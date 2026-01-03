<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:disable-auto-updates')]
class DisableAutoUpdatesCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:disable-auto-updates {plugins?*} {--all} {--enabled-only}';

	/** @var string */
	protected $description = 'Disables the auto-updates for a plugin.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$args = $this->argument('plugins');
		$all = $this->hasOption('all') && $this->option('all');
		$enabled_only  = $this->hasOption('enabled-only') && $this->option('enabled-only');

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

		$count = $successes = $already_disabled = 0;

		foreach ( $plugins as $plugin ) {
			$enabled = in_array( $plugin->FilePath, $auto_updates, true );

			if ( $enabled_only && !$enabled ) {
				continue;
			}

			++$count;

			if ( !$enabled ) {
				$this->warn(
					"Auto-updates already disabled for plugin {$plugin->Name}."
				);
				$already_disabled++;
			} else {
				$auto_updates = array_diff( $auto_updates, [ $plugin->FilePath ] );
				++$successes;
			}
		}

		if ( 0 === $count ) {
			$this->error(
				'No plugins provided to disable auto-updates for.'
			);
			return;
		}

		if ( count( $auto_updates ) > 0 ) {
			update_site_option( 'auto_update_plugins', $auto_updates );
		} else {
			delete_site_option( 'auto_update_plugins' );
		}

		$total = $plugins->count();

		$errors = $count - $successes;

		if ( $total > 1 ) {
			if ( $errors ) {
				if ( $successes ) {
					$message = $successes > 1 ? 'plugins' : 'plugin';
					$this->error( "Only disabled auto-updates for {$successes} of {$total} {$message}." );
				} else {
					$this->error( "No plugins auto-updates disabled." );
				}
			} else {
				$message = $successes > 1 ? 'plugins' : 'plugin';
				$this->info( "Disabled auto-updates for {$successes} of {$total} {$message}." );

				if ( $already_disabled ) {
					$message = $already_disabled > 1 ? 'plugins' : 'plugin';
					$this->info( "{$already_disabled} {$message} auto-updates already disabled." );
				}
			}
		}
	}
}