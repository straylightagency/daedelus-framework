<?php
namespace Daedelus\Foundation\Console\Commands\Cron\Event;

use Daedelus\Foundation\Console\Commands\Concerns\ManageCronEvents;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cron:event:delete')]
class DeleteCommand extends Command
{
	use ManageCronEvents;

	/** @var string */
	protected $signature = 'cron:event:delete {hooks?*} {--due-now} {--exclude=} {--all}';

	/** @var string */
	protected $description = 'Deletes all scheduled cron events for the given hook.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$hooks = $this->argument( 'hooks' );
		$due_now = $this->hasOption( 'due-now') && $this->option( 'due-now' );
		$all = $this->hasOption( 'all') && $this->option( 'all' );
		$exclude = explode( ',', (string) $this->option( 'exclude' ) );

		$events = $this->getSelectedCronEvents( $hooks, $due_now, $all, $exclude );

		if ( is_wp_error( $events ) ) {
			$this->error( $events ); 
			return;
		}

		$deleted = 0;
		foreach ( $events as $event ) {
			$cron = _get_cron_array();

			if ( ! isset( $cron[ $event->time ][ $event->hook ][ $event->sig ] ) ) {
				$this->warn( sprintf( "Failed to the delete the cron event '%s'.", $event->hook ) );
				continue;
			}

			wp_unschedule_event( $event->time, $event->hook, $event->args );

			++$deleted;
		}

		$message = sprintf( 'Deleted a total of %d cron %s.', $deleted, Str::plural( 'event', $deleted ) );
		
		$this->info( sprintf( $message, $deleted ) );
	}
}