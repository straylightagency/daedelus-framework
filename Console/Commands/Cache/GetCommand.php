<?php
namespace Daedelus\Foundation\Console\Commands\Cache;

use Illuminate\Console\Command;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cache:get')]
class GetCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:get {key} {group}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Gets a value from the object cache.';

	/**
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function handle(): void
	{
		$key = trim( $this->argument('key' ) );
		$group = trim( $this->argument('group' ) );

		$value = wp_cache_get( $key, $group );

		if ( false === $value ) {
			$this->error( "Object with key '$key' and group '$group' not found." );
			return;
		}

		$this->info( "$key: $value" );
	}
}