<?php

namespace Daedelus\Foundation\Console\Commands\Concerns;

use WP_Upgrader;

/**
 *
 */
trait ManageMaintenanceMode
{
	/**
	 * @return WP_Upgrader
	 */
	protected function getUpgrader(): WP_Upgrader
	{
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$upgrader = new WP_Upgrader();
		$upgrader->init();

		return $upgrader;
	}

	/**
	 * @param bool $enable
	 *
	 * @return void
	 */
	protected function setMaintenanceMode(bool $enable = false):void
	{
		$file = app()->publicPath('.maintenance');

		if ( $enable ) {
			// Create maintenance file to signal that we are upgrading.
			$maintenance_string = '<?php $upgrading = ' . time() . '; ?>';
			$this->files->delete( $file );
			$this->files->put( $file, $maintenance_string );
		} elseif ( $this->files->exists( $file ) ) {
			$this->files->delete( $file );
		}
	}
}