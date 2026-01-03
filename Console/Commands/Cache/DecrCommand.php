<?php
namespace Daedelus\Foundation\Console\Commands\Cache;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cache:decr')]
class DecrCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:decr {key} {offset} {group}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Decrements a value in the object cache.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$key = trim( $this->argument('key' ) );
		$offset = $this->argument('offset' );
		$group = trim( $this->argument('group' ) );

		$value = wp_cache_decr( $key, $offset, $group );

		if ( false === $value ) {
			$this->error( 'The value was not decremented.' );
			return;
		}

		$this->info( "$key: $value" );
	}
}