<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:is-active')]
class IsActivateCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:is-active {plugin} {--network}';

	/** @var string */
	protected $description = 'Checks if a given plugin is active.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$network_wide = $this->hasOption( 'network') && $this->option( 'network' );

		$plugin = $this->plugins()->first( fn ($plugin, $name) => $name === $this->argument( 'plugin' ) );

		if ( !$plugin ) {
			$this->line( 0 );

			return;
		}

		$this->line( $this->checkActive( $plugin->FilePath, $network_wide ) ? 1 : 0 );
	}
}