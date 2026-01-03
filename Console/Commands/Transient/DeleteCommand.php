<?php
namespace Daedelus\Foundation\Console\Commands\Transient;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'transient:delete')]
class DeleteCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'transient:delete {key?} {--all} {--network}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Deletes a transient value.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$key = $this->argument('key') ?? '';
		$all = $this->hasOption( 'all') && $this->option( 'all' );
		$expired = $this->hasOption( 'expired') && $this->option( 'expired' );
		$network = $this->hasOption( 'network') && $this->option( 'network' );

		if ( true === $all ) {
			$this->deleteAll( $network );
			return;
		}

		if ( true === $expired ) {
			$this->deleteExpired( $network );
			return;
		}

		if ( empty( $key ) ) {
			$this->error( 'Please specify transient key, or use --all or --expired.' );
			return;
		}

		$func = $network ? 'delete_site_transient' : 'delete_transient';

		if ( $func( $key ) ) {
			$this->info( 'Transient deleted.' );
		} else {
			$func = $network ? 'get_site_transient' : 'get_transient';

			if ( $func( $key ) ) {
				$this->error( 'Transient was not deleted even though the transient appears to exist.' );
			} else {
				$this->warn( 'Transient was not deleted; however, the transient does not appear to exist.' );
			}
		}
	}

	/**
	 * @param bool $network
	 *
	 * @return void
	 */
	protected function deleteAll(bool $network):void
	{
		if ( wp_using_ext_object_cache() ) {
			$this->warn( 'Transients are stored in an external object cache, and this command only deletes those stored in the database. You must flush the cache to delete all transients.' );
			return;
		}

		global $wpdb;

		$escape_like = fn ($text) => addcslashes( $text, '_%\\' );

		// To ensure proper count values we first delete all transients with a timeout
		// and then the remaining transients without a timeout.
		$count = 0;

		if ( ! $network ) {
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
						WHERE a.option_name LIKE %s
						AND a.option_name NOT LIKE %s
						AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )",
					$escape_like( '_transient_' ) . '%',
					$escape_like( '_transient_timeout_' ) . '%'
				)
			);

			$count += $deleted / 2; // Ignore affected rows for timeouts.

			$count += $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
					$escape_like( '_transient_' ) . '%'
				)
			);
		} elseif ( ! is_multisite() ) {
			// Non-Multisite stores site transients in the options table.
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
							WHERE a.option_name LIKE %s
							AND a.option_name NOT LIKE %s
							AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )",
					$escape_like( '_site_transient_' ) . '%',
					$escape_like( '_site_transient_timeout_' ) . '%'
				)
			);

			$count += $deleted / 2; // Ignore affected rows for timeouts.

			$count += $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
					$escape_like( '_site_transient_' ) . '%'
				)
			);
		} else {
			// Multisite stores site transients in the sitemeta table.
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
							WHERE a.meta_key LIKE %s
							AND a.meta_key NOT LIKE %s
							AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )",
					$escape_like( '_site_transient_' ) . '%',
					$escape_like( '_site_transient_timeout_' ) . '%'
				)
			);

			$count += $deleted / 2; // Ignore affected rows for timeouts.

			$count += $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE %s",
					$escape_like( '_site_transient_' ) . '%'
				)
			);
		}

		if ( $count > 0 ) {
			$this->info(
				sprintf(
					'%d %s deleted from the database.', $count, Str::plural( 'transient', $count )
				)
			);
		} else {
			$this->info( 'No transients found.' );
		}
	}

	/**
	 * @param bool $network
	 *
	 * @return void
	 */
	protected function deleteExpired(bool $network):void
	{
		if ( wp_using_ext_object_cache() ) {
			$this->warn( 'Transients are stored in an external object cache, and this command only deletes those stored in the database. You must flush the cache to delete all transients.' );
			return;
		}

		global $wpdb;

		$escape_like = fn ($text) => addcslashes( $text, '_%\\' );

		$count = 0;

		if ( ! $network ) {
			$count += $wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
						WHERE a.option_name LIKE %s
						AND a.option_name NOT LIKE %s
						AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
						AND b.option_value < %d",
					$escape_like( '_transient_' ) . '%',
					$escape_like( '_transient_timeout_' ) . '%',
					time()
				)
			);
		} elseif ( ! is_multisite() ) {
			// Non-Multisite stores site transients in the options table.
			$count += $wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
							WHERE a.option_name LIKE %s
							AND a.option_name NOT LIKE %s
							AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
							AND b.option_value < %d",
					$escape_like( '_site_transient_' ) . '%',
					$escape_like( '_site_transient_timeout_' ) . '%',
					time()
				)
			);
		} else {
			// Multisite stores site transients in the sitemeta table.
			$count += $wpdb->query(
				$wpdb->prepare(
					"DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
							WHERE a.meta_key LIKE %s
							AND a.meta_key NOT LIKE %s
							AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )
							AND b.meta_value < %d",
					$escape_like( '_site_transient_' ) . '%',
					$escape_like( '_site_transient_timeout_' ) . '%',
					time()
				)
			);
		}

		// The above queries delete the transient and the transient timeout
		// thus each transient is counted twice.
		$count = $count / 2;

		if ( $count > 0 ) {
			$this->info(
				sprintf(
					'%d %s deleted from the database.', $count, Str::plural( 'transient', $count )
				)
			);
		} else {
			$this->warn( 'No expired transients found.' );
		}
	}
}