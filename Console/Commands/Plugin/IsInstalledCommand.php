<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:is-installed')]
class IsInstalledCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:is-installed {plugin}';

	/** @var string */
	protected $description = 'Checks if a given plugin is installed.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$plugin = $this->plugins()->first( fn ($plugin, $name) => $name === $this->argument( 'plugin' ) );

		$this->line( !$plugin ? 0 : 1 );
	}
}