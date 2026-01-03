<?php

namespace Daedelus\Foundation\Console\Commands\Concerns\Utils;

use Illuminate\Console\Command;
use WP_Upgrader_Skin;

/**
 *
 */
class UpgraderSkin extends WP_Upgrader_Skin
{
	/** @var object  */
	public object $api;

	/** @var Command */
	protected Command $command;

	/**
	 * @param Command $command
	 * @param array $args
	 */
	public function __construct(Command $command, array $args = [])
	{
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$this->setCommandIo( $command );

		parent::__construct( $args );
	}

	/**
	 * @return void
	 */
	public function header() {}

	/**
	 * @return void
	 */
	public function footer() {}

	/**
	 * @return void
	 */
	public function bulk_header() {}

	/**
	 * @return void
	 */
	public function bulk_footer() {}

	/**
	 * @param Command $command
	 *
	 * @return void
	 */
	public function setCommandIo(Command $command):void
	{
		$this->command = $command;
	}

	/**
	 * Show error message.
	 *
	 * @param string $errors Error message.
	 *
	 * @return void
	 */
	public function error( $errors ): void
	{
		if ( ! $errors ) {
			return;
		}

		if ( is_string( $errors ) && isset( $this->upgrader->strings[ $errors ] ) ) {
			$errors = $this->upgrader->strings[ $errors ];
		}

		// TODO: show all errors, not just the first one
		$this->command->warn( $errors );
	}

	/**
	 * @param string $feedback
	 * @param array ...$args Optional text replacements.
	 */
	public function feedback( $feedback, ...$args ): void
	{
		$cache_manager = HttpCacheManager::getInstance();

		if ( 'parent_theme_prepare_install' === $feedback ) {
			$cache_manager->whitelistPackage( $this->api->download_link, 'theme', $this->api->slug, $this->api->version );
		}

		if ( isset( $this->upgrader->strings[ $feedback ] ) ) {
			$feedback = $this->upgrader->strings[ $feedback ];
		}

		if ( ! empty( $args ) && str_contains( $feedback, '%' ) ) {
			$feedback = vsprintf( $feedback, $args );
		}

		if ( empty( $feedback ) ) {
			return;
		}

		$feedback = str_replace( '&#8230;', '...', strip_tags( $feedback ) );
		$feedback = html_entity_decode( $feedback, ENT_QUOTES, get_bloginfo( 'charset' ) );

		$this->command->line( $feedback );
	}
}