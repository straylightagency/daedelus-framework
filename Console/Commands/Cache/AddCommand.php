<?php
namespace Daedelus\Foundation\Console\Commands\Cache;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cache:add')]
class AddCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:add {key} {value} {group} {expiration}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Adds a value to the object cache.';

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function handle(): void
	{
		$key = trim( $this->argument('key' ) );
		$value = $this->argument('value' );
		$group = trim( $this->argument('group' ) );
		$expiration = $this->argument('expiration' ) ?? 0;

		if ( !wp_cache_add( $key, $value, $group, $expiration ) ) {
			$this->error( "Could not add object '$key' in group '$group'. Does it already exist?" );
			return;
		}

		$this->line( "Added object '$key' in group '$group'." );
	}
}