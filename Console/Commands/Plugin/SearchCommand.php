<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:search')]
class SearchCommand extends Command
{
	/** @var string */
	protected $signature = 'plugin:search {search} {--per-page=20} {--page=1} {--fields=name,version,slug,ratings}';

	/** @var string */
	protected $description = 'Displays plugins in the WordPress.org plugin directory matching a given search query.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$search = $this->argument('search');
		$page = (int) $this->option('page');
		$per_page = (int) $this->option('per-page');
		$fields = array_fill_keys( explode( ',', $this->option('fields') ), true );

		$api = plugins_api( 'query_plugins', [
			'per_page' => $per_page,
			'page' => $page,
			'search' => $search,
			'fields' => $fields,
		] );

		if ( is_wp_error( $api ) ) {
			$this->error( $api->get_error_message() . __( ' Try again' ) );
		}

		if ( ! isset( $api->plugins ) ) {
			$this->error( __( 'API error. Try Again.' ) );
		}

		$plugins = $api->plugins;

		// Add `url` for plugin or theme on wordpress.org.
		foreach ( $plugins as $index => $item ) {
			if ( $item instanceof \stdClass ) {
				$item->url = "https://wordpress.org/plugins/{$item->slug}/";
			}
		}

		$this->info( sprintf( 'Showing %s of %s plugins.', count( $plugins ), $api->info['results'] ?? 'unknown total' ) );

		$results = $headers = [];

		$sanitize = function (string $value):string {
			$value = html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) );
			return Str::limit( $value, 90 );
		};

		foreach ( $plugins as $i => $plugin ) {
			$results[ $i ] = [];

			foreach ( $plugin as $field => $value ) {
				if ( in_array( $field, array_keys( $fields ) ) ) {
					if ( !is_array( $value ) ) {
						$results[ $i ][ $field ] = $sanitize( $value );

						if ( $i === 0 ) {
							$headers[] = ucfirst( $field );
						}
					} else {
						foreach ( $value as $k => $v ) {
							$results[ $i ][ $field . '_' . $k ] = $v;

							if ( $i === 0 ) {
								$headers[] = ucfirst( $field ) . ' ' . $k;
							}
						}
					}
				}
			}
		}

		$this->table( $headers, $results );
	}
}