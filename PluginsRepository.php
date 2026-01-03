<?php

namespace Daedelus\Foundation;

use Daedelus\Support\Actions;
use Daedelus\Support\Filters;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 *
 */
class PluginsRepository
{
	/** @var array */
	protected array $hiddenFiles = [];

	/** @var array */
	protected array $autoPlugins = [];

	/** @var array */
	protected array $plugins = [];

	/** @var array */
	protected array $activatedPlugins = [];

	/** @var array */
	protected array $cache = [];

	/** @var int */
	protected int $count = 0;

	/**
	 * @param Application $app
	 * @param Filesystem $files
	 */
	public function __construct(protected Application $app, protected Filesystem $files)
	{
	}

	/**
	 * @param string ...$hidden_files
	 *
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws InvalidArgumentException
	 * @throws NotFoundExceptionInterface
	 * @throws FileNotFoundException
	 */
	public function load(string ...$hidden_files):void
	{
		$this->hiddenFiles = $hidden_files;

		$this->check();
		$this->validate();
		$this->count();

		collect( $this->cache['plugins'] )->keys()->map( fn (string $path) => include_once $this->app->muPluginsPath( $path ) );

		Actions::add('plugins_loaded', [ $this, 'hooks' ], -9999);

		if ( is_admin() ) {
			Filters::add('show_advanced_plugins', [ $this, 'showInAdmin' ], 0, 2 );
		}
	}

	/**
	 * @return void
	 */
	public function hooks():void
	{
		foreach ( array_keys( $this->activatedPlugins ) as $plugin_file ) {
			Actions::do('activate_' . $plugin_file );
		}
	}

	/**
	 * @param bool $show
	 * @param string $type
	 *
	 * @return false|mixed
	 * @throws InvalidArgumentException
	 */
	public function showInAdmin(bool $show, string $type):mixed
	{
		$screen = get_current_screen();
		$current = is_multisite() ? 'plugins-network' : 'plugins';

		if ( $screen->base !== $current || $type !== 'mustuse' || !current_user_can('activate_plugins') ) {
			return $show;
		}

		$this->update();

		$GLOBALS['plugins']['mustuse'] = array_unique( array_merge( $this->autoPlugins, $this->plugins ), SORT_REGULAR );

		return false;
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws FileNotFoundException
	 */
	protected function check():void
	{
		$cached = $this->loadManifest();

		if ( !isset( $cached['plugins'], $cached['count'] ) || count( $cached['plugins'] ) !== $cached['count'] ) {
			$this->update();

			return;
		}

		$this->cache = $cached;
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 */
	protected function update():void
	{
		require_once public_path('wp-admin/includes/plugin.php');

		$this->autoPlugins = array_diff_key(
			get_plugins( '/../' . basename( $this->app->muPluginsPath() ) ),
			array_flip( array_map( 'basename', $this->hiddenFiles ) )
		);

		$this->plugins = array_diff_key(
			get_mu_plugins(),
			array_flip( array_map( 'basename', $this->hiddenFiles ) )
		);

		$plugins = array_diff_key( $this->autoPlugins, $this->plugins );

		$this->activatedPlugins = !isset( $this->cache[ 'plugins' ] ) ? $plugins : array_diff_key( $plugins, $this->cache['plugins'] );

		$this->cache = [ 'plugins' => $plugins, 'count' => $this->count() ];

		$this->writeManifest( $this->cache );
	}

	/**
	 * @return int
	 * @throws InvalidArgumentException
	 */
	protected function count():int
	{
		if ( !empty( $this->count ) ) {
			return $this->count;
		}

		$count = count( glob( $this->app->muPluginsPath( '*/' ), GLOB_ONLYDIR | GLOB_NOSORT ) );

		if ( !isset( $this->cache['count'] ) || $this->cache['count'] !== $count ) {
			$this->count = $count;

			$this->update();
		}

		return $this->count;
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 */
	protected function validate():void
	{
		foreach ( $this->cache['plugins'] as $plugin_file => $plugin_info ) {
			if ( !file_exists( $this->app->muPluginsPath( $plugin_file ) ) ) {
				$this->update();

				break;
			}
		}
	}

	/**
	 * Return plugins manifest.
	 *
	 * @return array|null
	 * @throws FileNotFoundException
	 */
	public function loadManifest(): ?array
	{
		$manifestPath = $this->app->getCachedPluginsPath();

		if ( $this->files->exists( $manifestPath ) ) {
			return $this->files->getRequire( $manifestPath );
		}

		return null;
	}

	/**
	 * @param array $manifest
	 *
	 * @return array
	 */
	protected function writeManifest(array $manifest): array
	{
		$this->files->put(
			$this->app->getCachedPluginsPath(),
			'<?php return ' . var_export( $manifest, true) . ';',
		);

		return $manifest;
	}
}