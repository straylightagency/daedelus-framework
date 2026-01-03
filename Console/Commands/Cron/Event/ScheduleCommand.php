<?php
namespace Daedelus\Foundation\Console\Commands\Cron\Event;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cron:event:schedule')]
class ScheduleCommand extends Command
{
	/** @var string */
	protected $signature = 'cron:event:schedule {hook} {next-run?} {recurrence?} {--args=}';

	/** @var string */
	protected $description = 'Schedules a new cron event.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$hook = (string) $this->argument( 'hook' );
		$next_run = $this->argument( 'next-run' ) ?? 'now';
		$recurrence = $this->argument( 'recurrence' ) ?? false;
		$args = array_filter( explode( ',', $this->option( 'args' ) ) );

		if ( empty( $next_run ) ) {
			$timestamp = time();
		} elseif ( is_numeric( $next_run ) ) {
			$timestamp = absint( $next_run );
		} else {
			$timestamp = strtotime( $next_run );
		}

		if ( ! $timestamp ) {
			$this->error( sprintf( "'%s' is not a valid datetime.", $next_run ) );
			return;
		}

		if ( ! empty( $recurrence ) ) {
			$schedules = wp_get_schedules();

			if ( ! isset( $schedules[ $recurrence ] ) ) {
				$this->error( sprintf( "'%s' is not a valid schedule name for recurrence.", $recurrence ) );
				return;
			}

			$event = wp_schedule_event( $timestamp, $recurrence, $hook, $args );
		} else {
			$event = wp_schedule_single_event( $timestamp, $hook, $args );
		}

		if ( false !== $event ) {
			$this->info( sprintf( "Scheduled event with hook '%s' for %s GMT.", $hook, date( 'Y-m-d H:i:s', $timestamp ) ) );
		} else {
			$this->error( 'Event not scheduled.' );
		}
	}
}