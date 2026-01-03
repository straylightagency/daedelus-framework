<?php
namespace Daedelus\Foundation\Console\Commands\Cron\Event;

use Daedelus\Foundation\Console\Commands\Concerns\ManageCronEvents;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cron:event:list')]
class ListCommand extends Command
{
	use ManageCronEvents;

	/** @var string */
	protected $signature = 'cron:event:list {--fields=} {--filters=}';

	/** @var string */
	protected $description = 'Lists scheduled cron events.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$fields = $this->option('fields') ?? [ 'hook', 'next_run_gmt', 'next_run_relative', 'recurrence', ];

		$filters = collect( explode(',', $this->option('filters') ) )
			->filter()
			->map( fn ($value) => explode( '=', $value ) )
			->mapWithKeys( fn ($values) => [ $values[0] => $values[1] ] )
			->reduce( fn (array $carry, $value, $key) => [...$carry, $key => $value ], [] );

		$events = $this->getCronEvents();

		if ( is_wp_error( $events ) ) {
			$events = [];
		}

		$events = json_decode( json_encode( $events ), true );
		$events = collect( $events )
			->map( fn ( $event ) => collect( $event )->only( $fields ) );

		foreach ( $filters as $key => $value ) {
			$events = $events->where( $key, $value );
		}

		$events->toArray();

		$this->table( $fields, $events );
	}
}