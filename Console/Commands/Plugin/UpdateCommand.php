<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Daedelus\Foundation\Console\Commands\Concerns\CanCallUpgrader;
use Daedelus\Foundation\Console\Commands\Concerns\CanRequestWpOrg;
use Daedelus\Foundation\Console\Commands\Concerns\CompareSemanticNamedVersion;
use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Daedelus\Foundation\Console\Commands\Concerns\Utils\HttpCacheManager;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use ReflectionException;
use SplFileObject;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:update')]
class UpdateCommand extends Command
{
	use ManagePlugins, CanRequestWpOrg, CompareSemanticNamedVersion, CanCallUpgrader;

	/** @var string */
	protected $signature = 'plugin:update {plugins?*} {--all} {--use=} {--force} {--minor} {--patch} {--dry-run} {--exclude=}';

	/** @var string */
	protected $description = 'Updates one or more plugins.';

	protected array $check_wporg = [
		'status' => false,
		'last_updated' => false,
	];

	protected array $check_headers  = [
		'tested_up_to' => false,
	];

	/** @var string */
	const string INVALID_VERSION_MESSAGE = 'version higher than expected';

	/**
	 * @return void
	 * @throws ReflectionException
	 */
	public function handle():void
	{
		$args = (array) $this->argument('plugins') ?? [];
		$all = $this->hasOption('all') && $this->option('all');
		$use = $this->option('use') ?? '';
		$force = $this->hasOption('force') && $this->option('force');
		$minor = $this->hasOption('minor') && $this->option('minor');
		$patch = $this->hasOption('patch') && $this->option('patch');
		$dry_run = $this->hasOption('dry-run') && $this->option('dry-run');
		$exclude = $this->option('exclude') ?? [];

		if ( empty( $args ) && !$all ) {
			return;
		}

		if ( $use ) {
			$plugins = $this->plugins();

			foreach ( $plugins as $name => $plugin ) {
				Artisan::call('plugin:install', [
					'plugins' => [ $name ],
					'--force' => true,
					'--use' => $use,
					'--minor' => $minor,
					'--patch' => $patch,
					'--dry-run' => $dry_run,
				] );
			}
		} else {
			$this->updateMany( $args, $all, $force, $minor, $patch, $dry_run, $exclude );
		}
	}

	/**
	 * @param array $args
	 * @param bool $all
	 * @param bool $force
	 * @param bool $minor
	 * @param bool $patch
	 * @param bool $dry_run
	 * @param array $exclude
	 *
	 * @return void
	 * @throws ReflectionException
	 */
	protected function updateMany(array $args, bool $all, bool $force, bool $minor, bool $patch, bool $dry_run, array $exclude): void
	{
		wp_update_plugins();

		if ( $minor && $patch ) {
			$this->error( '--minor and --patch cannot be used together.' );
			return;
		}

		$plugins = $this->getPluginsInfo();

		$errors = $skipped = 0;

		if ( ! $all ) {
			$plugins = $plugins->filter( fn ( $plugin, $name ) => in_array( $name, $args ) );

			$errors = count( $args ) - $plugins->count();
		}

		$plugins_to_update = $plugins->filter( fn ( $plugin ) => $plugin->Update === true );

		if ( $minor || $patch ) {
			$type = $minor ? 'minor' : 'patch';
			$plugins_to_update = $this->getMinorOrPatchUpdates( $plugins_to_update, $type, true );
		}

		// Check for items to update and remove extensions that have version higher than expected.
		foreach ( $plugins_to_update as $index => $plugin ) {
			if ( self::INVALID_VERSION_MESSAGE === $plugin->Update ) {
				$this->warn( "{$plugin->Name}: " . self::INVALID_VERSION_MESSAGE . '.' );

				++$skipped;

				$plugins_to_update->forget( $index );
			}
		}

		if ( $dry_run ) {
			if ( $plugins_to_update->isEmpty() ) {
				$this->line( "No plugins updates available." );

				if ( !empty( $exclude ) ) {
					$excluded = implode( ', ', $exclude );
					$this->line( "Skipped updates for: $excluded" );
				}

				return;
			}

			$this->line( "Available plugins updates:" );
			$this->table( [ 'name', 'status', 'version', 'update_version' ], $plugins_to_update );

			if ( !empty( $exclude ) ) {
				$excluded = implode( ', ', $exclude );
				$this->line( "Skipped updates for: $excluded" );
			}

			return;
		}

		$result = [];

		if ( $plugins_to_update->isNotEmpty() ) {
			$cache_manager = HttpCacheManager::getInstance();

			foreach ( $plugins_to_update as $plugin ) {
				$cache_manager->whitelistPackage( $plugin->UpdatePackage, 'plugin', $plugin->Name, $plugin->UpdateVersion );
			}

			$upgrader = $this->getPluginUpgrader( $force );

			// Ensure the upgrader uses the download offer present in each item.
			$transient_filter = function ( $transient ) use ( $plugins_to_update ) {
				foreach ( $plugins_to_update as $index => $plugin ) {
					if ( isset( $transient->response[ $index ] ) ) {
						if ( is_object( $transient->response[ $index ] ) ) {
							$transient->response[ $index ]->new_version = $plugin->UpdateVersion;
							$transient->response[ $index ]->package = $plugin->UpdatePackage;
						} else {
							$transient->response[ $index ]['new_version'] = $plugin->UpdateVersion;
							$transient->response[ $index ]['package'] = $plugin->UpdatePackage;
						}
					}
				}
				return $transient;
			};

			add_filter( 'site_transient_update_plugins', $transient_filter, 999 );
			$result = $upgrader->bulk_upgrade( $plugins_to_update->pluck('UpdateId') );
			remove_filter( 'site_transient_update_plugins', $transient_filter, 999 );
		}

		// Let the user know the results.
		$num_to_update = $plugins_to_update->count();

		$num_updated = count(
			array_filter( $result, fn ( $result ) => $result && ! is_wp_error( $result ) )
		);

		if ( $num_to_update > 0 ) {
			$status = [];

			foreach ( $plugins_to_update as $index => $plugin ) {
				$status[ $index ] = [
					'name' => $plugin->Name,
					'old_version' => $plugin->Version,
					'new_version' => $plugin->UpdateVersion,
					'status' => ( null !== $result[ $plugin->UpdateId ] && ! is_wp_error( $result[ $plugin->UpdateId ] ) ) ? 'Updated' : 'Error',
				];

				if ( null === $result[ $plugin->UpdateId ] || is_wp_error( $result[ $plugin->UpdateId ] ) ) {
					++$errors;
				}
			}

			$this->table( [ 'name', 'old_version', 'new_version', 'status' ], $status );
		}

		$total_updated = $all ? $num_to_update : count( $args );

		if ( 0 === $num_updated && $skipped ) {
			$errors = $skipped;
			$skipped = null;
		}

		if ( $errors ) {
			$failed_skipped_message = null === $skipped ? '' : " ({$errors} failed" . ( $skipped ? ", {$skipped} skipped" : '' ) . ')';

			if ( $num_updated ) {
				$this->error( "Only updated {$num_updated} of {$total_updated} plugins{$failed_skipped_message}." );
			} else {
				$this->error( "No plugins updated{$failed_skipped_message}." );
			}
		} else {
			$skipped_message = $skipped ? " ({$skipped} skipped)" : '';

			if ( $num_updated || $skipped ) {
				$this->info( "Updated {$num_updated} of {$total_updated} plugins{$skipped_message}." );
			} else {
				$message = $total_updated > 1 ? 'Plugins' : 'Plugin';
				$this->info( "{$message} already updated." );
			}
		}

		if ( !empty( $exclude ) ) {
			$excluded = implode( ', ', $exclude );
			$this->line( "Skipped updates for: $excluded" );
		}
	}

	/**
	 * @return Collection
	 */
	protected function getPluginsInfo(): Collection
	{
		$plugins = [];
		$duplicate_names = [];

		$auto_updates = get_site_option( 'auto_update_plugins' );

		if ( false === $auto_updates ) {
			$auto_updates = [];
		}

		$recently_active = is_network_admin() ? get_site_option( 'recently_activated' ) : get_option( 'recently_activated' );

		if ( false === $recently_active ) {
			$recently_active = [];
		}

		foreach ( $this->plugins() as $name => $plugin ) {
			$all_update_info = get_site_transient( 'update_plugins' );
			$update_info = ( isset( $all_update_info->response[ $name ] ) && null !== $all_update_info->response[ $name ] ) ? (array) $all_update_info->response[ $name ] : null;
			$wpOrg_info = $this->getWpOrgData( $name );

			if ( ! isset( $duplicate_names[ $name ] ) ) {
				$duplicate_names[ $name ] = [];
			}

			$duplicate_names[ $name ][] = $name;

			$plugins[ $name ] = (object) [
				'Name' => $name,
				'Status' => $this->getStatus( $name ),
				'Update' => (bool) $update_info,
				'UpdateVersion' => $update_info['new_version'] ?? null,
				'UpdatePackage' => $update_info['package'] ?? null,
				'Version' => $plugin->Version,
				'UpdateId' => $name,
				'Title' => $plugin->Name,
				'Description' => wordwrap( $plugin->Description ),
				'File' => $name,
				'AutoUpdate' => in_array( $name, $auto_updates, true ),
				'Author' => $plugin->Author,
				'TestedUpTo' => '',
				'WpOrgStatus' => $wpOrg_info['status'],
				'WpOrgLastUpdated' => $wpOrg_info['last_updated'],
				'RecentlyActive' => in_array( $name, array_keys( $recently_active ), true ),
			];

			if ( $this->check_headers['tested_up_to'] ) {
				$plugin_readme = app()->pluginsPath( dirname( $plugin->FilePath ) . '/readme.txt' );

				if ( file_exists( $plugin_readme ) && is_readable( $plugin_readme ) ) {
					$readme_obj = new SplFileObject( $plugin_readme );
					$readme_obj->setFlags( SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY );
					$readme_line = 0;

					// Reading the whole file can exhaust the memory, so only read the first 100 lines of the file,
					// as the "Tested up to" header should be near the top.
					while ( $readme_line < 100 && ! $readme_obj->eof() ) {
						$line = $readme_obj->fgets();

						// Similar to WP.org, it matches for both "Tested up to" and "Tested" header in the readme file.
						preg_match( '/^tested(:| up to:) (.*)$/i', strtolower( $line ), $matches );

						if ( ! empty( $matches[2] ) ) {
							$plugins[ $name ]->TestedUpTo = $matches[2];
							break;
						}

						++$readme_line;
					}
				}
			}

			if ( null === $update_info ) {
				// Get info for all plugins that don't have an update.
				$plugin_update_info = $all_update_info->no_update[ $name ] ?? null;

				// Compare version and update information in plugin list.
				if ( null !== $plugin_update_info && version_compare( $plugin->Version, $plugin_update_info->new_version, '>' ) ) {
					$plugins[ $name ]->Update = self::INVALID_VERSION_MESSAGE;
				}
			}
		}

		foreach ( $duplicate_names as $names ) {
			if ( count( $names ) <= 1 ) {
				continue;
			}

			foreach ( $names as $name ) {
				$plugins[ $name ]->Name = str_replace( '.' . pathinfo( $name, PATHINFO_EXTENSION ), '', $name );
			}
		}

		return collect( $plugins );
	}

	/**
	 * @param $plugin_name
	 *
	 * @return string[]
	 */
	protected function getWpOrgData( $plugin_name ): array
	{
		$data = [
			'status' => '',
			'last_updated' => '',
		];
		if ( ! $this->check_wporg['status'] && ! $this->check_wporg['last_updated'] ) {
			return $data;
		}

		if ( $this->check_wporg ) {
			try {
				$plugin_data = $this->getPluginInfo( $plugin_name );
			} catch ( Exception $e ) {
				// Request failed. The plugin is not (active) on .org.
				$plugin_data = false;
			}

			if ( $plugin_data ) {
				$data['status'] = 'active';

				if ( ! $this->check_wporg['last_updated'] ) {
					return $data; // The plugin is active on .org, but we don't need the date.
				}
			}
			// Just because the plugin is not in the api, does not mean it was never on .org.
		}

		/** @todo use Illuminate\Http\Client\Request instead of wp_remote_get */
		$request = wp_remote_get( "https://plugins.trac.wordpress.org/log/{$plugin_name}/?limit=1&mode=stop_on_copy&format=rss" );
		$response_code = wp_remote_retrieve_response_code( $request );

		if ( 404 === $response_code ) {
			return $data; // This plugin was never on .org, there is no date to check.
		}

		if ( 'active' !== $data['status'] ) {
			$data['status'] = 'closed'; // This plugin was on .org at some point, but not anymore.
		}

		// Check the last update date.
		$body = wp_remote_retrieve_body( $request );

		if ( str_contains( $body, 'pubDate' ) ) {
			// Very raw check, not validating the format or anything else.
			$xml = simplexml_load_string( $body );
			$xml_pub_date = $xml->xpath( '//pubDate' );

			if ( $xml_pub_date ) {
				$data['last_updated'] = wp_date( 'Y-m-d', (string) strtotime( $xml_pub_date[0] ) );
			}
		}

		return $data;
	}

	/**
	 * @param Collection $plugins
	 * @param string $type
	 * @param bool $require_stable
	 *
	 * @return Collection
	 */
	protected function getMinorOrPatchUpdates(Collection $plugins, string $type, bool $require_stable): Collection
	{
		foreach ( $plugins->values() as $index => $plugin ) {
			try {
				$data = $this->getPluginInfo( $plugin->name, 'en_US', [ 'versions' => true ] );
			} catch ( Exception $exception ) {
				$plugins->forget( $index );

				continue;
			}

			// No minor or patch versions to access.
			if ( empty( $data['versions'] ) ) {
				$plugins->forget( $index );

				continue;
			}

			$update_version = $update_package = false;

			foreach ( $data['versions'] as $version => $download_link ) {
				try {
					$update_type = $this->compareSemanticNamedVersion( $version, $plugin->Version );
				} catch ( Exception $e ) {
					continue;
				}

				// Compared version must be older.
				if ( ! $update_type ) {
					continue;
				}

				// Only permit 'patch' for 'patch'.
				if ( 'patch' === $type && 'patch' !== $update_type ) {
					continue;
				}

				// Permit 'minor' or 'patch' for 'minor' phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- False positive.
				if ( 'minor' === $type && ! in_array( $update_type, [ 'minor', 'patch' ], true ) ) {
					continue;
				}

				if ( $require_stable && 'stable' !== VersionParser::parseStability( $version ) ) {
					continue;
				}

				if ( $update_version && ! Comparator::greaterThan( $version, $update_version ) ) {
					continue;
				}

				$update_version = $version;
				$update_package = $download_link;
			}

			// If there's not a matching version, bail on updates.
			if ( ! $update_version ) {
				$plugins->forget( $index );

				continue;
			}

			$plugins[ $index ]->UpdateVersion = $update_version;
			$plugins[ $index ]->UpdatePackage = $update_package;
		}

		return $plugins;
	}
}