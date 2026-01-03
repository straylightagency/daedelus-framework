<?php

namespace Daedelus\Foundation\Console\Commands\Concerns;

use Illuminate\Support\Str;
use stdClass;
use WP_Error;

/**
 *
 */
trait ManageCronEvents
{
	/**
	 * @param array $args
	 * @param bool $due_now
	 * @param bool $all
	 * @param array $exclude
	 *
	 * @return array|false
	 */
	protected function getSelectedCronEvents(array $args, bool $due_now, bool $all, array $exclude = []):array|false
	{
		if ( empty( $args ) && ! $due_now && ! $all ) {
			$this->error( 'Please specify one or more cron events, or use --due-now/--all.' );
			return false;
		}

		if ( ! empty( $args ) && $all ) {
			$this->error( 'Please either specify cron events, or use --all.' );
			return false;
		}

		if ( $due_now && $all ) {
			$this->error( 'Please use either --due-now or --all.' );
			return false;
		}

		$events = $this->getCronEvents();

		if ( is_wp_error( $events ) ) {
			return $events;
		}

		$hooks = wp_list_pluck( $events, 'hook' );

		foreach ( $args as $hook ) {
			if ( ! in_array( $hook, $hooks, true ) ) {
				$this->error( sprintf( "Invalid cron event '%s'", $hook ) );
				return false;
			}
		}

		// Remove all excluded hooks.
		if ( ! empty( $exclude ) ) {
			$events = array_filter(
				$events,
				fn ( $event ) => ! in_array( $event->hook, $exclude, true )
			);
		}

		// If --due-now is specified, take only the events that have 'now' as
		// their next_run_relative time.
		if ( $due_now ) {
			$due_events = [];

			foreach ( $events as $event ) {
				if ( ! empty( $args ) && ! in_array( $event->hook, $args, true ) ) {
					continue;
				}

				if ( 'now' === $event->next_run_relative ) {
					$due_events[] = $event;
				}
			}

			$events = $due_events;
		} elseif ( ! $all ) {
			// If --all is not specified, take only the events that have been
			// given as $args.
			$due_events = [];

			foreach ( $events as $event ) {
				if ( in_array( $event->hook, $args, true ) ) {
					$due_events[] = $event;
				}
			}

			$events = $due_events;
		}

		return $events;
	}



	/**
	 * @param bool $is_due_now
	 *
	 * @return array|WP_Error
	 */
	protected function getCronEvents(bool $is_due_now = false): WP_Error|array
	{
		// wp_get_ready_cron_jobs since 5.1.0
		$jobs = $is_due_now ? wp_get_ready_cron_jobs() : _get_cron_array();

		$events = [];

		if ( empty( $jobs ) && ! $is_due_now ) {
			return new WP_Error(
				'no_events',
				'You currently have no scheduled cron events.'
			);
		}

		foreach ( $jobs as $time => $hooks ) {
			// Incorrectly registered cron events can produce a string key.

			if ( is_string( $time ) ) {
				$this->warn( sprintf( 'Ignoring incorrectly registered cron event "%s".', $time ) );
				continue;
			}

			foreach ( $hooks as $hook => $hook_events ) {
				foreach ( $hook_events as $sig => $data ) {
					$events[] = (object) [
						'hook' => $hook,
						'time' => $time,
						'sig' => $sig,
						'args' => implode(',', $data['args'] ),
						'schedule' => $data['schedule'],
						'interval' => $data['interval'],
					];

				}
			}
		}

		return array_map( function (stdClass $event) {
			$schedules = wp_get_schedules();
			$event->recurrence = ( isset( $schedules[ $event->schedule ] ) ) ? $this->interval( $event->interval ) : 'Non-repeating';
			$event->next_run = get_date_from_gmt( date( 'Y-m-d H:i:s', $event->time ), 'Y-m-d H:i:s' );
			$event->next_run_gmt = date( 'Y-m-d H:i:s', $event->time );
			$event->next_run_relative = $this->interval( $event->time - time() );

			return $event;
		}, $events );
	}

	/**
	 * @param int $since
	 *
	 * @return string
	 */
	protected function interval(int $since): string
	{
		if ( $since <= 0 ) {
			return 'now';
		}

		$since = absint( $since );

		// Array of time period chunks.
		$chunks = [
			[ 60 * 60 * 24 * 365, 'year' ],
			[ 60 * 60 * 24 * 30, 'month' ],
			[ 60 * 60 * 24 * 7, 'week' ],
			[ 60 * 60 * 24, 'day' ],
			[ 60 * 60, 'hour' ],
			[ 60, 'minute' ],
			[ 1, 'second' ],
		];

		// we only want to output two chunks of time here, eg:
		// x years, xx months
		// x days, xx hours
		// so there's only two bits of calculation below:

		// step one: the first chunk

		$count = $seconds = 0;
		$name = '';

		for ( $i = 0, $j = count( $chunks ); $i < $j; $i++ ) {
			$seconds = $chunks[ $i ][0];
			$name = $chunks[ $i ][1];

			// Finding the biggest chunk (if the chunk fits, break).
			$count = floor( $since / $seconds );

			if ( floatval( 0 ) !== $count ) {
				break;
			}
		}

		// Set output var.
		$output = sprintf( '%d %s', $count, Str::plural( $name, absint( $count ) ) );

		// Step two: the second chunk.
		if ( $i + 1 < $j ) {
			$seconds2 = $chunks[ $i + 1 ][0];
			$name2 = $chunks[ $i + 1 ][1];

			$count2 = floor( ( $since - ( $seconds * $count ) ) / $seconds2 );

			if ( floatval( 0 ) !== $count2 ) {
				// Add to output var.
				$output .= ' ' . sprintf( '%d %s', $count2, Str::plural( $name2, absint( $count2 ) ) );
			}
		}

		return $output;
	}
}