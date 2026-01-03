<?php

namespace Daedelus\Foundation\Console\Commands\Concerns;

use Daedelus\Foundation\Console\Commands\Concerns\Utils\DestructivePluginUpgrader;
use Illuminate\Support\Collection;
use Plugin_Upgrader;
use ReflectionException;

/**
 *
 */
trait ManagePlugins
{
	/**
	 * @param string $plugin
	 *
	 * @return string
	 */
	protected function getStatus(string $plugin): string
	{
		if ( is_plugin_active_for_network( $plugin ) ) {
			return 'active-network';
		}

		if ( is_plugin_active( $plugin ) ) {
			return 'active';
		}

		return 'inactive';
	}

	/**
	 * @param string $plugin
	 * @param bool $network_wide
	 *
	 * @return bool
	 */
	protected function checkActive( string $plugin, bool $network_wide): bool
	{
		$required = $network_wide ? 'active-network' : 'active';

		return $required === $this->getStatus( $plugin );
	}

	/**
	 * @return Collection
	 */
	protected function plugins(): Collection
	{
		$collection = collect( apply_filters( 'all_plugins', get_plugins() ) );

		return $collection->map( function (array $plugin, string $file_path) {
			$plugin['BaseName'] = basename( $file_path, '.php' );
			$plugin['FilePath'] = $file_path;

			return (object) $plugin;
		} )->keyBy( fn ($plugin, string $file_path) => basename( dirname( $file_path ) ) );
	}

	/**
	 * @param bool $force
	 *
	 * @return Plugin_Upgrader
	 * @throws ReflectionException
	 */
	protected function getPluginUpgrader(bool $force): Plugin_Upgrader
	{
		return $this->getUpgrader( $force ? DestructivePluginUpgrader::class : Plugin_Upgrader::class );
	}
}