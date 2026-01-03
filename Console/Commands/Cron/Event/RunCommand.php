<?php
namespace Daedelus\Foundation\Console\Commands\Cron\Event;

use Daedelus\Foundation\Console\Commands\Concerns\ManageCronEvents;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cron:event:run')]
class RunCommand extends Command
{
	use ManageCronEvents;

	/** @var string */
	protected $signature = 'cron:event:run {hooks*} {--due-now} {--exclude=} {--all}';

	/** @var string */
	protected $description = 'Runs the next scheduled cron event for the given hook.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$hooks = $this->argument( 'hooks' );
		$due_now = $this->hasOption( 'due-now') && $this->option( 'due-now' );
		$all = $this->hasOption( 'all') && $this->option( 'all' );
		$exclude = array_filter( explode( ',', $this->option( 'exclude' ) ) );

		$events = $this->getSelectedCronEvents( $hooks, $due_now, $all, $exclude );

		if ( is_wp_error( $events ) ) {
			$this->error( $events );
		}

		$executed = 0;
		foreach ( $events as $event ) {
			$start  = microtime( true );

			if ( ! defined( 'DOING_CRON' ) ) {
				define( 'DOING_CRON', true );
			}

			if ( false !== $event->schedule ) {
				wp_reschedule_event( $event->time, $event->schedule, $event->hook, $event->args );
			}

			wp_unschedule_event( $event->time, $event->hook, $event->args );

			do_action_ref_array( $event->hook, $event->args );

			$total = round( microtime( true ) - $start, 3 );

			++$executed;

			$this->line( sprintf( "Executed the cron event '%s' in %ss.", $event->hook, $total ) );
		}

		$message = ( 1 === $executed ) ? 'Executed a total of %d cron event.' : 'Executed a total of %d cron events.';
		$this->info( sprintf( $message, $executed ) );
	}
}