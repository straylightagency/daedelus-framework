<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\CanCallUpgrader;
use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Daedelus\Foundation\Console\Commands\Concerns\Utils\HttpCacheManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use ReflectionException;
use Symfony\Component\Console\Attribute\AsCommand;
use WP_Error;
use function is_wp_error;
use function wp_remote_get;
use function wp_remote_retrieve_body;

/**
 *
 */
#[AsCommand(name: 'plugin:install')]
class InstallCommand extends Command
{
	use ManagePlugins, CanCallUpgrader;

	/** @var string */
	protected $signature = 'plugin:install {plugins?*} {--use=} {--force} {--minor} {--patch} {--dry-run} {--activate} {--activate-network}';

	/** @var string */
	protected $description = 'Installs one or more plugins.';

	/** @var string */
	const string GITHUB_LATEST_RELEASE_URL = '/^https:\/\/github\.com\/(.*)\/releases\/latest\/?$/';

	/** @var string */
	const string GITHUB_RELEASE_API_ENDPOINT = 'https://api.github.com/repos/%s/releases';

	/**
	 * @return void
	 * @throws ReflectionException
	 */
	public function handle():void
	{
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$args = (array) $this->argument('plugins') ?? [];
		$use = $this->option('use') ?? '';
		$force = $this->hasOption('force') && $this->option('force');
		$minor = $this->hasOption('minor') && $this->option('minor');
		$patch = $this->hasOption('patch') && $this->option('patch');
		$dry_run = $this->hasOption('dry-run') && $this->option('dry-run');
		$activate = $this->hasOption('activate') && $this->option('activate');
		$activate_network = $this->hasOption('activate-network') && $this->option('activate-network');

		$successes = $errors = 0;

		foreach ( $args as $slug ) {
			if ( empty( $slug ) ) {
				$this->warn( 'Ignoring ambiguous empty slug value.' );
				continue;
			}

			$result = false;

			$is_remote = str_contains( $slug, '://' );

			if ( $is_remote ) {
				$github_repo = $this->getGithubRepoFromReleaseUrl( $slug );

				if ( $github_repo ) {
					$version = $this->getTheLatestGithubVersion( $github_repo );

					if ( is_wp_error( $version ) ) {
						$this->error( $version->get_error_message() );
					}

					/**
					 * Sets the $slug that will trigger the installation based on a zip file.
					 */
					$slug = $version['url'];

					$this->line( 'Latest release resolved to ' . $version['name'] );
				}
			}

			// Check if a URL to a remote or local zip has been specified.
			if ( $is_remote || ( pathinfo( $slug, PATHINFO_EXTENSION ) === 'zip' && is_file( $slug ) ) ) {
				// Install from local or remote zip file.
				$file_upgrader = $this->getPluginUpgrader( $force );

				$filter = false;
				// If a GitHub URL, do some guessing as to the correct plugin/theme directory.
				if ( $is_remote && 'github.com' === wp_parse_url( $slug, PHP_URL_HOST )
				     // Don't attempt to rename ZIPs uploaded to the releases page or coming from a raw source.
				     && ! preg_match( '#github\.com/[^/]+/[^/]+/(?:releases/download|raw)/#', $slug ) ) {

					$filter = function ( $source ) use ( $slug ) {
						$slug_dir = basename( wp_parse_url( $slug, PHP_URL_PATH ), '.zip' );

						// Don't use the zip name if archive attached to release, as name likely to contain version tag/branch.
						if ( preg_match( '#github\.com/[^/]+/([^/]+)/archive/#', $slug, $matches ) ) {
							// Note this will be wrong if the project name isn't the same as the plugin/theme slug name.
							$slug_dir = $matches[1];
						}

						$source_dir = basename( trim( $source, '\/' ) ); // `$source` is trailing-slashed path to the unzipped archive directory, so basename returns the unslashed directory.
						if ( $source_dir === $slug_dir ) {
							return $source;
						}
						$new_path = substr_replace( $source, $slug_dir, strrpos( $source, $source_dir ), strlen( $source_dir ) );

						if ( $GLOBALS['wp_filesystem']->move( $source, $new_path ) ) {
							$this->line( sprintf( "Renamed Github-based project from '%s' to '%s'.", $source_dir, $slug_dir ) );
							return $new_path;
						}

						$this->error( "Couldn't move Github-based project to appropriate directory." );
						return '';
					};

					add_filter( 'upgrader_source_selection', $filter, 10 );
				}

				if ( $file_upgrader->install( $slug ) ) {
					$slug = $file_upgrader->result['destination_name'];
					$result = true;
					if ( $filter ) {
						remove_filter( 'upgrader_source_selection', $filter, 10 );
					}
					++$successes;
				} else {
					++$errors;
				}
			} else {
				// Assume a plugin slug from the WordPress.org repository has been specified.
				$result = $this->installFromRepo( $slug, $use, $force );

				if ( is_null( $result ) ) {
					++$errors;
				} elseif ( is_wp_error( $result ) ) {
					$key = $result->get_error_code();
					if ( in_array( $key, [ 'plugins_api_failed', 'themes_api_failed' ], true )
					     && ! empty( $result->error_data[ $key ] ) && in_array( $result->error_data[ $key ], [ 'N;', 'b:0;' ], true ) ) {
						$this->warn( "Couldn't find '$slug' in the WordPress.org plugin directory." );

						++$errors;
					} else {
						$this->warn( "$slug: " . $result->get_error_message() );

						if ( 'already_installed' !== $key ) {
							++$errors;
						}
					}
				} else {
					++$successes;
				}
			}

			$plugin = $this->plugins()->first( fn ($plugin, string $name) => $name === $slug );

			// If installation goes well $result will be true.
			$allow_activation = $result;

			// Allow installation for installed extension.
			if ( is_wp_error( $result ) && 'already_installed' === $result->get_error_code() ) {
				$allow_activation = true;
			}

			if ( true === $allow_activation && $plugin ) {
				if ( $activate_network ) {
					$this->line( "Network-activating '$slug'..." );

					Artisan::call( 'plugin:activate', [
						'plugins' => [ $slug ],
						'--network' => true,
					] );
				}

				if ( $activate ) {
					$this->line( "Activating '$slug'..." );

					Artisan::call( 'plugin:activate', [
						'plugins' => [ $slug ],
					] );
				}
			}
		}

		$total_installed = count( $args );

		if ( $errors ) {
			if ( $successes ) {
				$message = $total_installed > 1 ? 'plugins' : 'plugin';
				$this->error( "Only installed {$successes} of {$total_installed} {$message}." );
			} else {
				$this->error( "No plugins installed." );
			}
		} else {
			if ( $successes ) {
				$message = $total_installed > 1 ? 'plugins' : 'plugin';
				$this->info( "Installed {$successes} of {$total_installed} {$message}." );
			} else {
				$message = $successes > 1 ? 'Plugins' : 'Plugin';
				$this->info( "{$message} already installed." );
			}
		}
	}

	/**
	 * @param string $slug
	 * @param string|null $version
	 * @param bool $force
	 *
	 * @return bool|array|WP_Error|null
	 * @throws ReflectionException
	 */
	protected function installFromRepo(string $slug, string $version = null, bool $force = false): null|bool|array|WP_Error
	{
		$response = plugins_api( 'plugin_information', [ 'slug' => $slug ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $version ) {
			$this->alterApiResponse( $response, $version );
		}

		$status = install_plugin_install_status( $response );

		if ( ! $force && 'install' !== $status['status'] ) {
			return new WP_Error( 'already_installed', 'Plugin already installed.' );
		}

		$this->line( sprintf( 'Installing %s (%s)', html_entity_decode( $response->name, ENT_QUOTES ), $response->version ) );

		if ( $version !== 'dev' ) {
			$cache_manager = HttpCacheManager::getInstance();
			$cache_manager->whitelistPackage( $response->download_link, 'plugin', $response->slug, $response->version );
		}

		return $this->getPluginUpgrader( $force )->install( $response->download_link );
	}

	/**
	 * @param object $response
	 * @param string $version
	 *
	 * @return void
	 */
	protected function alterApiResponse(object $response, string $version):void
	{
		if ( $response->version === $version ) {
			return;
		}

		// WordPress.org forces https, but still sometimes returns http
		// See https://twitter.com/nacin/status/512362694205140992
		$response->download_link = str_replace( 'http://', 'https://', $response->download_link );

		list( $link ) = explode( $response->slug, $response->download_link );

		if ( 'dev' === $version ) {
			$response->download_link = $link . $response->slug . '.zip';
			$response->version = 'Development Version';
		} else {
			$response->download_link = $link . $response->slug . '.' . $version . '.zip';
			$response->version = $version;

			// Check if the requested version exists.
			$response = wp_remote_head( $response->download_link );
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 !== (int) $response_code ) {
				if ( is_wp_error( $response ) ) {
					$error_msg = $response->get_error_message();
				} else {
					$error_msg = sprintf( 'HTTP code %d', $response_code );
				}
				$this->error(
					sprintf(
						"Can't find the requested plugin version %s in the WordPress.org plugin repository (%s).",
						$version,
						$error_msg
					)
				);
			}
		}
	}

	/**
	 * Get the latest package version based on a given repo slug.
	 *
	 * @param string $repo_slug
	 *
	 * @return array{ name: string, url: string }|WP_Error
	 */
	protected function getTheLatestGithubVersion(string $repo_slug ): WP_Error|array
	{
		$api_url = sprintf( self::GITHUB_RELEASE_API_ENDPOINT, $repo_slug );
		$token = getenv( 'GITHUB_TOKEN' );

		$request_arguments = $token ? [ 'headers' => 'Authorization: Bearer ' . getenv( 'GITHUB_TOKEN' ) ] : [];

		$response = wp_remote_get( $api_url, $request_arguments );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$decoded_body = json_decode( $body );

		// WP_Http::FORBIDDEN doesn't exist in WordPress 3.7
		if ( 403 === wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error(
				403,
				$decoded_body->message . PHP_EOL . $decoded_body->documentation_url . PHP_EOL .
				'In order to pass the token to WP-CLI, you need to use the GITHUB_TOKEN environment variable.'
			);
		}

		if ( null === $decoded_body ) {
			return new WP_Error( 500, 'Empty response received from GitHub.com API' );
		}

		if ( ! isset( $decoded_body[0] ) ) {
			return new WP_Error( '400', 'The given Github repository does not have any releases' );
		}

		$latest_release = $decoded_body[0];

		return [
			'name' => $latest_release->name,
			'url'  => $this->getAssetUrlFromRelease( $latest_release ),
		];
	}

	/**
	 * Get the asset URL from the release array. When the asset is not present, we fall back to the zipball_url (source code) property.
	 *
	 * @param object $release
	 *
	 * @return string|null
	 */
	protected function getAssetUrlFromRelease(object $release):?string
	{
		if ( isset( $release->assets[0]->browser_download_url ) ) {
			return $release->assets[0]->browser_download_url;
		}

		if ( isset( $release->zipball_url ) ) {
			return $release->zipball_url;
		}

		return null;
	}

	/**
	 * Get the GitHub repo from the URL.
	 *
	 * @param string $url
	 *
	 * @return string|null
	 */
	protected function getGithubRepoFromReleaseUrl(string $url): ?string {
		preg_match( self::GITHUB_LATEST_RELEASE_URL, $url, $matches );

		return $matches[1] ?? null;
	}
}