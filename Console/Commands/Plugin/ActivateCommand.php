<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Daedelus\Foundation\Console\Commands\Concerns\ManagePlugins;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:activate')]
class ActivateCommand extends Command
{
	use ManagePlugins;

	/** @var string */
	protected $signature = 'plugin:activate {plugins?*} {--all} {--network}';

	/** @var string */
	protected $description = 'Activates one or more plugins.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$args = $this->argument('plugins');
		$all = $this->hasOption( 'all') && $this->option( 'all' );
		$network_wide = $this->hasOption( 'network') && $this->option( 'network' );

		if ( empty( $args ) && !$all ) {
			return;
		}

		$successes = $errors = $already_activated = 0;

		$plugins = $this->plugins();

		if ( !$all ) {
			$plugins = $plugins->filter( fn ( $plugin, $name ) => in_array( $name, $args ) );
		}

		if ( $plugins->count() < count( $args ) ) {
			$errors = count( $args ) - $plugins->count();
		}

		foreach ( $plugins as $plugin ) {
			$status = $this->getStatus( $plugin->FilePath );

			if ( $all && in_array( $status, [ 'active', 'active-network' ], true ) ) {
				$already_activated++;
				continue;
			}

			// Network-active is the highest level of activation status.
			if ( 'active-network' === $status ) {
				$this->warn( "Plugin '{$plugin->Name}' is already network active." );
				continue;
			}

			// Don't reactivate active plugins, but do let them become network-active.
			if ( !$network_wide && 'active' === $status ) {
				$this->warn( "Plugin '{$plugin->Name}' is already active." );
				$already_activated++;
				continue;
			}

			// Plugins need to be deactivated before being network activated.
			if ( $network_wide && 'active' === $status ) {
				deactivate_plugins( $plugin->FilePath, false, false );
			}

			$result = activate_plugin( $plugin->FilePath, '', $network_wide );

			if ( is_wp_error( $result ) ) {
				$message = $result->get_error_message();
				$message = preg_replace( '/<a\s[^>]+>.*<\/a>/im', '', $message );
				$message = wp_strip_all_tags( $message );
				$message = str_replace( 'Error: ', '', $message );

				$this->warn( "Failed to activate plugin. {$message}" );

				++$errors;
			} else {
				$network_wide = $network_wide || ( is_multisite() && is_network_only_plugin( $plugin->FilePath ) );

				if ( ( $network_wide ? 'active-network' : 'active' ) === $this->getStatus( $plugin->FilePath ) ) {
					if ( $network_wide ) {
						$this->line( "Plugin '{$plugin->Name}' network activated." );
					} else {
						$this->line( "Plugin '{$plugin->Name}' activated." );
					}
				} else {
					$this->warn( "Could not activate the '{$plugin->Name}' plugin." );
				}

				++$successes;
			}
		}

		$total = $plugins->count();

		if ( $total > 1 ) {
			if ( $errors ) {
				if ( $successes ) {
					$message = $successes > 1 ? 'plugins' : 'plugin';
					$this->error( "Only activated {$successes} of {$total} {$message}." );
				} else {
					$this->error( "No plugins activated." );
				}
			} else {
				$message = $successes > 1 ? 'plugins' : 'plugin';
				$this->info( "Activated {$successes} of {$total} {$message}." );

				if ( $already_activated ) {
					$message = $already_activated > 1 ? 'plugins' : 'plugin';
					$this->info( "{$already_activated} {$message} already activated." );
				}
			}
		}
	}
}