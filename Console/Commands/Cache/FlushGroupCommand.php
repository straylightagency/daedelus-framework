<?php
namespace Daedelus\Foundation\Console\Commands\Cache;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cache:flush-group')]
class FlushGroupCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:flush-group {group}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Removes all cache items in a group, if the object cache implementation supports it.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$group = trim( $this->argument('group' ) );

		if ( !function_exists( 'wp_cache_supports' ) || !wp_cache_supports( 'flush_group' ) ) {
			$this->error( 'Group flushing is not supported.' );
			return;
		}

		if ( !wp_cache_flush_group( $group ) ) {
			$this->error( "Cache group '$group' was not flushed." );
			return;
		}

		$this->line( "Cache group '$group' was flushed." );
	}
}