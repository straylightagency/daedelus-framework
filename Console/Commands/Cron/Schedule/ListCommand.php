<?php
namespace Daedelus\Foundation\Console\Commands\Cron\Schedule;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cron:schedule:list')]
class ListCommand extends Command
{
	/** @var string */
	protected $signature = 'cron:schedule:list {--fields=} {--field}';

	/** @var string */
	protected $description = 'List available cron schedules.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$fields = $this->option('fields') ?? ['name', 'display', 'interval'];

		$schedules = wp_get_schedules();
		if ( ! empty( $schedules ) ) {
			uasort( $schedules, fn (array $a, array $b) => $a['interval'] - $b['interval'] );
			$schedules = array_map( fn (array $schedule, $name) => [...$schedule, 'name' => $name ], $schedules, array_keys( $schedules ) );
		}

		$schedules = array_map( function ($schedule) use ($fields) {
			$sorting = [];

			foreach ( $fields as $key ) {
				$sorting[$key] = $schedule[$key];
			}

			return $sorting;
		},  $schedules );

		$this->table( $fields, $schedules );
	}
}