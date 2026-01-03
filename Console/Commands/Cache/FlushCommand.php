<?php
namespace Daedelus\Foundation\Console\Commands\Cache;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cache:flush')]
class FlushCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:flush';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Flushes the object cache.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$value = wp_cache_flush();

		if ( false === $value ) {
			$this->error( 'The object cache could not be flushed.' );
			return;
		}

		$this->line( 'The cache was flushed.' );
	}
}