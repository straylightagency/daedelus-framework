<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:status-auto-updates')]
class StatusAutoUpdatesCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:status-auto-updates {plugins?*} {--all} {--enabled-only} {--disabled-only}';

	/** @var string */
	protected $description = 'Shows the status of auto-updates for a plugin.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$args = (array) $this->argument('plugins') ?? [];
		$all = $this->hasOption('all') && $this->option('all');
		$enabled_only = $this->hasOption('enabled-only') && $this->option('enabled-only');
		$disabled_only = $this->hasOption('disabled-only') && $this->option('disabled-only');

		if ( $enabled_only && $disabled_only ) {
			$this->error(
				'--enabled-only and --disabled-only are mutually exclusive and '
				. 'cannot be used at the same time.'
			);
			return;
		}

		if ( empty( $args ) && !$all ) {
			return;
		}

		$plugins = $this->plugins();

		if ( !$all ) {
			$plugins = $plugins->filter( fn ( $plugin, $name ) => in_array( $name, $args ) );
		}

		$auto_updates = get_site_option( 'auto_update_plugins' );

		if ( false === $auto_updates ) {
			$auto_updates = [];
		}

		$results = [];

		foreach ( $plugins as $name => $plugin ) {
			$enabled = in_array( $plugin->FilePath, $auto_updates, true );

			if ( $enabled_only && ! $enabled ) {
				continue;
			}

			if ( $disabled_only && $enabled ) {
				continue;
			}

			$results[] = [
				$name,
				$plugin->Name,
				$enabled ? 'enabled' : 'disabled',
			];
		}

		$this->table( ['Slug', 'Name', 'Status'], $results );
	}
}