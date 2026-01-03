<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:deactivate')]
class DeactivateCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:deactivate {plugins?*} {--all} {--network}';

	/** @var string */
	protected $description = 'Deactivates one or more plugins.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$args = $this->argument('plugins');
		$network_wide = $this->hasOption( 'network') && $this->option( 'network' );
		$all = $this->hasOption( 'all') && $this->option( 'all' );
		$uninstall = $this->hasOption( 'uninstall') && $this->option( 'uninstall' );

		if ( empty( $args ) && !$all ) {
			return;
		}

		$successes = $errors = $already_deactivated = 0;

		$plugins = $this->plugins();

		if ( !$all ) {
			$plugins = $plugins->filter( fn ( $plugin, $name ) => in_array( $name, $args ) );
		}

		if ( $plugins->count() < count( $args ) ) {
			$errors = count( $args ) - $plugins->count();
		}

		foreach ( $plugins as $plugin ) {
			$status = $this->getStatus( $plugin->FilePath );

			if ( $all && ! in_array( $status, [ 'active', 'active-network' ], true ) ) {
				$already_deactivated++;
				continue;
			}

			// Network active plugins must be explicitly deactivated.
			if ( ! $network_wide && 'active-network' === $status ) {
				$this->warn( "Plugin '{$plugin->Name}' is network active and must be deactivated with --network flag." );
				++$errors;
				continue;
			}

			if ( ! in_array( $status, [ 'active', 'active-network' ], true ) ) {
				$this->warn( "Plugin '{$plugin->Name}' isn't active." );
				$already_deactivated++;
				continue;
			}

			deactivate_plugins( $plugin->FilePath, false, $network_wide );

			if ( ! is_network_admin() ) {
				update_option(
					'recently_activated',
					[ $plugin->FilePath => time() ] + (array) get_option( 'recently_activated' )
				);
			} else {
				update_site_option(
					'recently_activated',
					[ $plugin->FilePath => time() ] + (array) get_site_option( 'recently_activated' )
				);
			}

			if ( 'inactive' === $this->getStatus( $plugin->FilePath ) ) {
				if ( $network_wide ) {
					$this->line( "Plugin '{$plugin->Name}' network deactivated." );
				} else {
					$this->line( "Plugin '{$plugin->Name}' deactivated." );
				}
			} else {
				$this->warn( "Could not deactivate the '{$plugin->Name}' plugin." );
			}

			++$successes;

			if ( $uninstall ) {
				$this->line( "Uninstalling '{$plugin->Name}'..." );

				Artisan::call( 'plugin:uninstall', [
					'plugins' => [ $plugin->Name ]
				] );
			}
		}

		$total = $plugins->count();

		if ( $total > 1 ) {
			if ( $errors ) {
				if ( $successes ) {
					$message = $successes > 1 ? 'plugins' : 'plugin';
					$this->error( "Only deactivated {$successes} of {$total} {$message}." );
				} else {
					$this->error( "No plugins deactivated." );
				}
			} else {
				$message = $successes > 1 ? 'plugins' : 'plugin';
				$this->info( "Deactivated {$successes} of {$total} {$message}." );

				if ( $already_deactivated ) {
					$message = $already_deactivated > 1 ? 'plugins' : 'plugin';
					$this->info( "{$already_deactivated} {$message} already deactivated." );
				}
			}
		}
	}
}