<?php

namespace Daedelus\Foundation\Console\Commands\Concerns\Utils;

use Plugin_Upgrader;
use WP_Error;

/**
 * A plugin upgrader class that clears the destination directory.
 */
class DestructivePluginUpgrader extends Plugin_Upgrader {

	/**
	 * @param $args
	 *
	 * @return WP_Error|bool|array|string
	 */
	public function install_package( $args = [] ): WP_Error|bool|array|string
	{
		parent::upgrade_strings(); // Needed for the 'remove_old' string.

		$args['clear_destination'] = true;
		$args['abort_if_destination_exists'] = false;

		return parent::install_package( $args );
	}
}