<?php

namespace Daedelus\Framework\Console\Commands\Concerns;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
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

		$upgrader = new WP_Upgrader;
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
            // To avoid WP error when the maintenance is on, we add a filter to check if we are running in console.
			$maintenance_string = '<?php $upgrading = ' . time() . '; add_filter("enable_maintenance_mode", fn () => !app()->runningInConsole() ); ?>';
			$this->files->delete( $file );
			$this->files->put( $file, $maintenance_string );
		} elseif ( $this->files->exists( $file ) ) {
			$this->files->delete( $file );
		}
	}

    /**
     * @return bool
     * @throws FileNotFoundException
     */
    protected function getMaintenanceStatus():bool
    {
        $file = app()->publicPath('.maintenance');

        if ( ! $this->files->exists( $file ) ) {
            return false;
        }

        $upgrading = 0;

        $contents = $this->files->get( $file );
        $matches  = [];

        if ( preg_match( '/upgrading\s*=\s*(\d+)\s*;/i', $contents, $matches ) ) {
            $upgrading = (int) $matches[1];
        } else {
            $this->warn('Unable to read the maintenance file timestamp, non-numeric value detected.');
        }

        return time() - $upgrading >= 10 * MINUTE_IN_SECONDS;
    }
}