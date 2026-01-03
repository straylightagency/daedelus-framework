<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:enable-auto-updates')]
class EnableAutoUpdatesCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:enable-auto-updates {plugins?*} {--all} {--disabled-only}';

	/** @var string */
	protected $description = 'Enables the auto-updates for a plugin.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$args = $this->argument('plugins');
		$all = $this->hasOption('all') && $this->option('all');
		$disabled_only = $this->hasOption('disabled-only') && $this->option('disabled-only');

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

		$count = $successes = $already_enabled = 0;

		foreach ( $plugins as $plugin ) {
			$enabled = in_array( $plugin->FilePath, $auto_updates, true );

			if ( $disabled_only && $enabled ) {
				continue;
			}

			++$count;

			if ( $enabled ) {
				$this->warn(
					"Auto-updates already enabled for plugin {$plugin->Name}."
				);
				$already_enabled++;
			} else {
				$auto_updates[] = $plugin->FilePath;
				++$successes;
			}
		}

		if ( 0 === $count ) {
			$this->error(
				'No plugins provided to enable auto-updates for.'
			);
			return;
		}

		update_site_option( 'auto_update_plugins', $auto_updates );

		$total = $plugins->count();

		$errors = $count - $successes;

		if ( $total > 1 ) {
			if ( $errors ) {
				if ( $successes ) {
					$message = $successes > 1 ? 'plugins' : 'plugin';
					$this->error( "Only enabled auto-updates for {$successes} of {$total} {$message}." );
				} else {
					$this->error( "No plugins auto-updates enabled." );
				}
			} else {
				$message = $successes > 1 ? 'plugins' : 'plugin';
				$this->info( "Enabled auto-updates for {$successes} of {$total} {$message}." );

				if ( $already_enabled ) {
					$message = $already_enabled > 1 ? 'plugins' : 'plugin';
					$this->info( "{$already_enabled} {$message} auto-updates already enabled." );
				}
			}
		}
	}
}