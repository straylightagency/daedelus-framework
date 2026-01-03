<?php
namespace Daedelus\Foundation\Console\Commands\Cron\Event;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cron:event:unschedule')]
class UnscheduleCommand extends Command
{
	/** @var string */
	protected $signature = 'cron:event:unschedule {hook}';

	/** @var string */
	protected $description = 'Unschedules all cron events for a given hook.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$hook = $this->argument('hook');

		$unscheduled = wp_unschedule_hook( $hook );

		if ( empty( $unscheduled ) ) {
			$message = 'Failed to unschedule events for hook \'%1\$s.';

			// If 0 event found on hook.
			if ( 0 === $unscheduled ) {
				$message = "No events found for hook '%1\$s'.";
			}

			$this->error( sprintf( $message, $hook ) );
		} else {
			$this->info(
				sprintf(
					'Unscheduled %1$d %2$s for hook \'%3$s\'.',
					$unscheduled, Str::plural( 'event', $unscheduled ), $hook
				)
			);
		}
	}
}