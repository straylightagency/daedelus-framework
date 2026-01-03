<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:status')]
class StatusCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:status {plugins?*}';

	/** @var string */
	protected $description = 'Reveals the status of one or all plugins.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$args = (array) $this->argument('plugins') ?? [];

		$plugins = $this->plugins();

		if ( !empty( $args ) ) {
			$plugins = $this->plugins()->filter( fn ($plugin) => in_array( $plugin->BaseName, $args ) );
		}

		$plugins = $plugins->map(function ($plugin) {
			$plugin->Status = $this->getStatus( $plugin->FilePath );

			return $plugin;
		} );

		$count = $plugins->count();

		$this->line(
			sprintf( '%d installed %s:', $count, ( $count > 1 ? 'plugins' : 'plugin' ) )
		);

		$plugins = $plugins->select( [ 'BaseName', 'Name', 'Status', 'Version', 'Author', ] );

		$this->table( [
			'Slug', 'Name', 'Status', 'Version', 'Author'
		], $plugins );
	}
}