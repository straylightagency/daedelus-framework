<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Daedelus\Foundation\Console\Commands\Concerns\ManageThemes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:update')]
class UpdateCommand extends Command
{
	use ManageThemes;

	// Invalid version message.
	const string INVALID_VERSION_MESSAGE = 'version higher than expected';

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:update {themes?*} {--all} {--version} {--minor} {--patch} {--dry-run} {--insecure}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Updates one or more themes.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$args = $this->argument('themes');
		$all = $this->hasOption('all') && $this->option('all');
		$version = $this->option('version');
		$minor = $this->hasOption('minor') && $this->option('minor');
		$patch = $this->hasOption('patch') && $this->option('patch');
		$dryRun = $this->hasOption('dry-run') && $this->option('dry-run');
		$insecure = $this->hasOption('insecure') && $this->option('insecure');

		if ( empty( $args ) && !$all ) {
			$this->error( 'Please specify one or more themes, or use --all.' );
			return;
		}

		if ( $all ) {
			$themes = $this->findAll();
		} else {
			$themes = $this->findMany( $args );
		}

		if ( $version && $dryRun ) {
			$this->error( '--dry-run cannot be used together with --version.' );
			return;
		}

		if ( $version ) {
			foreach ( $themes as $theme ) {
				Artisan::call('theme:install', [
					'themes' => [$theme->stylesheet],
					'--force' => true,
					'--version' => $version,
					'--minor' => $minor,
					'--patch' => $patch,
					'--dry-run' => $dryRun,
					'--insecure' => $insecure,
				] );
			}
		} else {
			$this->updateMany();
		}
	}

	/**
	 * @return void
	 */
	protected function updateMany():void
	{
		$args = $this->argument('themes');

		$all = $this->hasOption( 'all' ) && $this->option( 'all' );
		$minor = $this->hasOption( 'minor' ) && $this->option( 'minor' );
		$patch = $this->hasOption( 'patch' ) && $this->option( 'patch' );
		$insecure = $this->hasOption( 'insecure' ) && $this->option( 'insecure' );
		$version = $this->option( 'version' );
		$dryRun = $this->hasOption( 'dry-run' ) && $this->option( 'dry-run' );

		call_user_func( 'wp_update_themes' );

		if ( ! empty( $assoc_args['format'] ) && in_array( $assoc_args['format'], [ 'json', 'csv' ], true ) ) {
			$logger = new Loggers\Quiet( $this->get_runner()->in_color() );
			$this->set_logger( $logger );
		}

		if ( $all && empty( $args ) ) {
			$this->error( "Please specify one or more themes, or use --all." );
		}

		if ( $minor && $patch ) {
			$this->error( '--minor and --patch cannot be used together.' );
		}

		$items = $this->findAll();

		$errors = $skipped = 0;

		if ( $all ) {
			$items  = $this->filter_item_list( $items, $args );
			$errors = count( $args ) - count( $items );
		}

		$items_to_update = wp_list_filter( $items, [ 'update' => true ] );

		if ( $minor || $patch ) {
			$type = $minor ? 'minor' : 'patch';

			$items_to_update = self::get_minor_or_patch_updates( $items_to_update, $type, $insecure, true, 'theme' );
		}

		// Check for items to update and remove extensions that have version higher than expected.
		foreach ( $items_to_update as $item_key => $item_info ) {
			if ( static::INVALID_VERSION_MESSAGE === $item_info['update'] ) {
				$this->warn( "{$item_info['name']}: " . static::INVALID_VERSION_MESSAGE . '.' );
				++$skipped;
				unset( $items_to_update[ $item_key ] );
			}
		}

		if ( $dryRun ) {
			if ( empty( $items_to_update ) ) {
				$this->info( "No theme updates available." );

				return;
			}

			if ( ! empty( $assoc_args['format'] ) && in_array( $assoc_args['format'], [ 'json', 'csv' ], true ) ) {
				Utils\format_items( $assoc_args['format'], $items_to_update, [ 'name', 'status', 'version', 'update_version' ] );
			} elseif ( ! empty( $assoc_args['format'] ) && 'summary' === $assoc_args['format'] ) {
				$this->info( "Available theme updates:" );
				foreach ( $items_to_update as $item_to_update => $info ) {
					$this->info( "{$info['title']} update from version {$info['version']} to version {$info['update_version']}" );
				}
			} else {
				$this->info( "Available theme updates:" );
				Utils\format_items( 'table', $items_to_update, [ 'name', 'status', 'version', 'update_version' ] );
			}

			return;
		}

		$result = [];

		// Only attempt to update if there is something to update.
		if ( ! empty( $items_to_update ) ) {
			$cache_manager = $this->get_http_cache_manager();

			foreach ( $items_to_update as $item ) {
				$cache_manager->whitelist_package( $item['update_package'], 'theme', $item['name'], $item['update_version'] );
			}

			$upgrader = $this->get_upgrader( $assoc_args );

			// Ensure the upgrader uses the download offer present in each item.
			$transient_filter = function ( $transient ) use ( $items_to_update ) {
				foreach ( $items_to_update as $name => $item_data ) {
					if ( isset( $transient->response[ $name ] ) ) {
						if ( is_object( $transient->response[ $name ] ) ) {
							$transient->response[ $name ]->new_version = $item_data['update_version'];
							$transient->response[ $name ]->package     = $item_data['update_package'];
						} else {
							$transient->response[ $name ]['new_version'] = $item_data['update_version'];
							$transient->response[ $name ]['package']     = $item_data['update_package'];
						}
					}
				}
				return $transient;
			};

			add_filter( 'site_transient_update_themes', $transient_filter, 999 );

			$result = $upgrader->bulk_upgrade( wp_list_pluck( $items_to_update, 'update_id' ) );

			remove_filter( 'site_transient_update_themes', $transient_filter, 999 );
		}

		// Let the user know the results.
		$num_to_update = count( $items_to_update );

		if ( $num_to_update > 0 ) {
			if ( ! empty( $assoc_args['format'] ) && 'summary' === $assoc_args['format'] ) {
				foreach ( $items_to_update as $item_to_update => $info ) {
					$message = null !== $result[ $info['update_id'] ] ? 'updated successfully' : 'did not update';
					$this->info( "{$info['title']} {$message} from version {$info['version']} to version {$info['update_version']}" );
				}
			} else {
				$status = [];

				foreach ( $items_to_update as $item_to_update => $info ) {
					$status[ $item_to_update ] = [
						'name'        => $info['name'],
						'old_version' => $info['version'],
						'new_version' => $info['update_version'],
						'status'      => ( null !== $result[ $info['update_id'] ] && ! is_wp_error( $result[ $info['update_id'] ] ) ) ? 'Updated' : 'Error',
					];

					if ( null === $result[ $info['update_id'] ] || is_wp_error( $result[ $info['update_id'] ] ) ) {
						++$errors;
					}
				}

				$format = 'table';

				if ( ! empty( $assoc_args['format'] ) && in_array( $assoc_args['format'], [ 'json', 'csv' ], true ) ) {
					$format = $assoc_args['format'];
				}

				Utils\format_items( $format, $status, [ 'name', 'old_version', 'new_version', 'status' ] );
			}
		}
	}
}