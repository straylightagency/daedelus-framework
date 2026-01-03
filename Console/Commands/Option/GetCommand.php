<?php
namespace Daedelus\Foundation\Console\Commands\Option;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'option:get')]
class GetCommand extends Command
{
	/** @var string */
	protected $signature = 'option:get {key}';

	/** @var string */
	protected $description = 'Gets the value for an option.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$key = $this->argument('key');

		$value = get_option( $key );

		if ( false === $value ) {
			$this->error( "Could not get '{$key}' option. Does it exist?" );
			return;
		}

		if ( is_array( $value ) ) {
			$keys = array_keys( $value );
			$values = array_map( function ( $value ) {
				$value = is_array( $value ) ? json_encode( $value ) : $value;
				return Str::limit( $value, 100 );
			}, array_values( $value ) );

			if ( array_is_list( $values ) ) {
				foreach ( $values as $v ) {
					$this->line( $v );
				}
			} else {
				$this->table( $keys, [ $values ] );
			}

			return;
		}

		$this->line( $value );
	}
}