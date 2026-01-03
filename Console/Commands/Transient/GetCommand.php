<?php
namespace Daedelus\Foundation\Console\Commands\Transient;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'transient:get')]
class GetCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'transient:get {key} {--network}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Gets a transient value.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$key = $this->argument('key');
		$network = $this->hasOption('network') && $this->option('network');

		$func = $network ? 'get_site_transient' : 'get_transient';

		$value = $func( $key );

		if ( false === $value ) {
			$this->error( 'Transient with key "' . $key . '" is not set.' );
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