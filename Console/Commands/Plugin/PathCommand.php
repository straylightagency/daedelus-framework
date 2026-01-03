<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:path')]
class PathCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:path {plugin?} {--dir}';

	/** @var string */
	protected $description = 'Gets the path to a plugin or to the plugin directory.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$path = untrailingslashit( app()->pluginsPath() );

		$plugin_name = (string) $this->argument('plugin');
		$dir = $this->hasOption('dir') && $this->option('dir');

		if ( ! empty( $plugin_name ) ) {
			$plugin = $this->plugins()->first( fn ($plugin, string $name) => $name === $plugin_name );

			$path = app()->pluginsPath( $plugin->FilePath );

			if ( $dir ) {
				$path = dirname( $path );
			}
		}

		$this->line( $path );
	}
}