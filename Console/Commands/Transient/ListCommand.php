<?php
namespace Daedelus\Foundation\Console\Commands\Transient;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'transient:list')]
class ListCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'transient:list {--search=} {--exclude=} {--fields=} {--network}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Lists transients and their values.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		global $wpdb;

		$escape_like = fn ($text) => addcslashes( $text, '_%\\' );

		$search = $this->option('search') ? $escape_like( $this->option('search') ) : '%';
		$exclude = $this->option('exclude') ? $escape_like( $this->option('exclude') ) : '';
		$fields = $this->option('fields') ? explode(',', $this->option('fields') ) : [ 'name', 'value', 'expiration' ];
		$network = $this->hasOption('network') && $this->option('network');

		if ( wp_using_ext_object_cache() ) {
			$this->error( 'Transients are stored in an external object cache, and this command only shows those stored in the database.' );
			return;
		}

		// Substitute wildcards.
		$search = str_replace(
			[ '*', '?' ],
			[ '%', '_' ],
			$search
		);

		// Substitute wildcards.
		$exclude = str_replace(
			[ '*', '?' ],
			[ '%', '_' ],
			$exclude
		);

		if ( $network ) {
			if ( is_multisite() ) {
				$where = $wpdb->prepare(
					'WHERE `meta_key` LIKE %s',
					$escape_like( '_site_transient_' ) . $search
				);
				$where .= $wpdb->prepare(
					' AND meta_key NOT LIKE %s',
					$escape_like( '_site_transient_timeout_' ) . '%'
				);
				if ( $exclude ) {
					$where .= $wpdb->prepare(
						' AND meta_key NOT LIKE %s',
						$escape_like( '_site_transient_' ) . $exclude
					);
				}

				$query = "SELECT `meta_key` as `name`, `meta_value` as `value` FROM {$wpdb->sitemeta} {$where}";
			} else {
				$where = $wpdb->prepare(
					'WHERE `option_name` LIKE %s',
					$escape_like( '_site_transient_' ) . $search
				);
				$where .= $wpdb->prepare(
					' AND option_name NOT LIKE %s',
					$escape_like( '_site_transient_timeout_' ) . '%'
				);
				if ( $exclude ) {
					$where .= $wpdb->prepare(
						' AND option_name NOT LIKE %s',
						$escape_like( '_site_transient_' ) . $exclude
					);
				}

				$query = "SELECT `option_name` as `name`, `option_value` as `value` FROM {$wpdb->options} {$where}";
			}
		} else {
			$where = $wpdb->prepare(
				'WHERE `option_name` LIKE %s',
				$escape_like( '_transient_' ) . $search
			);
			$where .= $wpdb->prepare(
				' AND option_name NOT LIKE %s',
				$escape_like( '_transient_timeout_' ) . '%'
			);
			if ( $exclude ) {
				$where .= $wpdb->prepare(
					' AND option_name NOT LIKE %s',
					$escape_like( '_transient_' ) . $exclude
				);
			}

			$query = "SELECT `option_name` as `name`, `option_value` as `value` FROM {$wpdb->options} {$where}";
		}

		$results = $wpdb->get_results( $query );

		foreach ( $results as $result ) {
			$result->name = str_replace( [ '_site_transient_', '_transient_' ], '', $result->name );
			$result->expiration = $this->getTransientExpiration( $result->name, $network );
			$result->value = maybe_unserialize( $result->value );
		}

		$results = array_map( function ($result) {
			$value = is_array( $result->value ) ? json_encode( $result->value ) : $result->value;

			return [
				'name' => $result->name,
				'value' => Str::limit( $value, 100 ),
				'expiration' => $result->expiration,
			];
		}, $results );

		$this->table( $fields, $results );
	}

	/**
	 * Retrieves the expiration time.
	 *
	 * @param string $name
	 * @param bool $is_site_transient
	 *
	 * @return string Expiration time string.
	 */
	private function getTransientExpiration(string $name, bool $is_site_transient = false ): string {
		if ( $is_site_transient ) {
			if ( is_multisite() ) {
				$expiration = (int) get_site_option( '_site_transient_timeout_' . $name );
			} else {
				$expiration = (int) get_option( '_site_transient_timeout_' . $name );
			}
		} else {
			$expiration = (int) get_option( '_transient_timeout_' . $name );
		}

		if ( 0 === $expiration ) {
			return 'never expires';
		}

		$now = time();

		if ( $now > $expiration ) {
			return 'expired';
		}

		return human_time_diff( $now, $expiration );
	}
}