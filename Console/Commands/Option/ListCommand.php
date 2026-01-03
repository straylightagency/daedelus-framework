<?php
namespace Daedelus\Foundation\Console\Commands\Option;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'option:list')]
class ListCommand extends Command
{
	/** @var string */
	protected $signature = 'option:list {--search=} {--exclude=} {--fields=} {--transients} {--autoload=} {--orderby=} {--order=}';

	/** @var string */
	protected $description = 'Lists options and their values.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		global $wpdb;

		$escape_like = fn ($text) => addcslashes( $text, '_%\\' );

		$search = $this->option('search') ? $escape_like( $this->option('search') ) : '%';
		$exclude = $this->option('exclude') ? $escape_like( $this->option('exclude') ) : '';
		$fields = $this->option('fields') ? explode( ',', $this->option('fields') ) : [ 'option_name', 'option_value' ];
		$show_transients = $this->hasOption('transients') && $this->option('transients');
		$autoload = $this->option('autoload');
		$orderby = $this->option('orderby') ?? 'option_id';
		$order = $this->option('order') ?? 'desc';

		$size_query = ',LENGTH(option_value) AS `size_bytes`';
		$autoload_query = '';

		// Substitute wildcards.
		$search = str_replace(
			[ '*', '?' ],
			[ '%', '_' ],
			$search
		);

		// Substitute wildcards.
		$exclude = str_replace(
			[ '*', '?' ],
			[ '%', '_' ],
			$exclude
		);

		if ( !empty( $autoload ) ) {
			if ( 'on' === $autoload || 'yes' === $autoload ) {
				$autoload_query = " AND autoload='yes'";
			} elseif ( 'off' === $autoload || 'no' === $autoload ) {
				$autoload_query = " AND autoload='no'";
			} else {
				$this->error( "Value of '--autoload' should be 'on', 'off', 'yes', or 'no'." );
				return;
			}
		}

		// By default, we don't want to display transients.
		if ( $show_transients ) {
			$transients_query = " AND option_name LIKE '\_transient\_%'
			OR option_name LIKE '\_site\_transient\_%'";
		} else {
			$transients_query = " AND option_name NOT LIKE '\_transient\_%'
			AND option_name NOT LIKE '\_site\_transient\_%'";
		}

		$where = '';
		if ( $search ) {
			$where .= $wpdb->prepare( 'WHERE `option_name` LIKE %s', $search );
		}

		if ( $exclude ) {
			$where .= $wpdb->prepare( ' AND `option_name` NOT LIKE %s', $exclude );
		}

		$where .= $autoload_query . $transients_query;

		// phpcs:disable WordPress.DB.PreparedSQL -- Hardcoded query parts without user input.
		$options = $wpdb->get_results(
			'SELECT `option_name`,`option_value`,`autoload`' . $size_query
			. " FROM `$wpdb->options` {$where}"
		);
		// phpcs:enable

		// Sort result.
		if ( 'option_id' !== $orderby ) {
			usort(
				$options,
				function ( $a, $b ) use ( $orderby, $order ) {
					// Sort array.
					return 'asc' === $order
						? $a->$orderby > $b->$orderby
						: $a->$orderby < $b->$orderby;
				}
			);
		}

		if ( 'option_id' === $orderby && 'desc' === $order ) { // Sort by default descending.
			krsort( $options );
		}

		foreach ( $options as $v ) {
			if ( ! empty( $v->option_value ) ) {
				$v->option_value = maybe_unserialize( $v->option_value );
			}

			$v->option_value = is_array( $v->option_value ) ? json_encode( $v->option_value ) : $v->option_value;
			$v->option_value = Str::limit( $v->option_value, 100 );
		}

		$options = json_decode( json_encode( $options ), true );
		$options = collect( $options )
			->map( fn ( $option ) => collect( $option )->only( $fields ) )
			->toArray();

		$this->table( $fields, $options );
	}
}