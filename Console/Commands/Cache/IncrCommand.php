<?php
namespace Daedelus\Foundation\Console\Commands\Cache;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cache:incr')]
class IncrCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:incr {key} {offset} {group}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Increments a value in the object cache.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$key = trim( $this->argument('key' ) );
		$offset = $this->argument('offset' );
		$group = trim( $this->argument('group' ) );

		$value = wp_cache_incr( $key, $offset, $group );

		if ( false === $value ) {
			$this->error( 'The value was not incremented.' );
			return;
		}

		$this->info( "$key: $value" );
	}
}