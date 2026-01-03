<?php
namespace Daedelus\Foundation\Console\Commands\Cron;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use WP_Error;

/**
 *
 */
#[AsCommand(name: 'cron:test')]
class TestCommand extends Command
{
	/** @var string */
	protected $signature = 'cron:test';

	/** @var string */
	protected $description = 'Tests the WP Cron spawning system and reports back its status.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			$this->error( 'The DISABLE_WP_CRON constant is set to true. WP-Cron spawning is disabled.' );
			return;
		}

		if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
			$this->warn( 'The ALTERNATE_WP_CRON constant is set to true. WP-Cron spawning is not asynchronous.' );
		}

		$spawn = $this->getCronSpawn();

		if ( is_wp_error( $spawn ) ) {
			$this->error( sprintf( 'WP-Cron spawn failed with error: %s', $spawn->get_error_message() ) );
			return;
		}

		$code = wp_remote_retrieve_response_code( $spawn );

		if ( 200 === $code ) {
			$this->info( 'WP-Cron spawning is working as expected.' );
		} else {
			$message = wp_remote_retrieve_response_message( $spawn );

			$this->error( sprintf( 'WP-Cron spawn returned HTTP status code: %1$s %2$s', $code, $message ) );
		}
	}

	/**
	 * @return WP_Error|array
	 */
	protected function getCronSpawn(): WP_Error|array
	{
		$doing_wp_cron = sprintf( '%.22F', microtime( true ) );

		$cron_request_array = [
			'url'  => site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron ),
			'key'  => $doing_wp_cron,
			'args' => [
				'timeout'   => 3,
				'blocking'  => true,
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native WordPress hook.
				'sslverify' => apply_filters( 'https_local_ssl_verify', true ),
			],
		];

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native WordPress hook.
		$cron_request = apply_filters( 'cron_request', $cron_request_array );

		# Enforce a blocking request in case something that's hooked onto the 'cron_request' filter sets it to false
		$cron_request['args']['blocking'] = true;

		return wp_remote_post( $cron_request['url'], $cron_request['args'] );
	}
}