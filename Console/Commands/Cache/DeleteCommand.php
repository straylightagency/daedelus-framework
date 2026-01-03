<?php
namespace Daedelus\Foundation\Console\Commands\Cache;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cache:delete')]
class DeleteCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:delete {key} {group}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Removes a value from the object cache.';

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function handle(): void
	{
		$key = trim( $this->argument('key' ) );
		$group = trim( $this->argument('group' ) );

		$result = wp_cache_delete( $key, $group );

		if ( false === $result ) {
			$this->error( 'The object was not deleted.' );
			return;
		}

		$this->line( 'Object deleted.' );
	}
}