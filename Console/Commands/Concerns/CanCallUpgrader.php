<?php

namespace Daedelus\Foundation\Console\Commands\Concerns;

use Daedelus\Foundation\Console\Commands\Concerns\Utils\UpgraderSkin;
use Plugin_Upgrader;
use ReflectionException;

/**
 *
 */
trait CanCallUpgrader
{
	/**
	 * @param string $class_name
	 *
	 * @return Plugin_Upgrader
	 * @throws ReflectionException
	 */
	protected function getUpgrader(string $class_name): Plugin_Upgrader
	{
		if ( ! class_exists( '\WP_Upgrader' ) ) {
			if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' ) ) {
				include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
		}

		if ( ! class_exists( '\WP_Upgrader_Skin' ) ) {
			if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php' ) ) {
				include ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
			}
		}

		$skin = new UpgraderSkin( $this );

		return new $class_name( $skin );
	}
}