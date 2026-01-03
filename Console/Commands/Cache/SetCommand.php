<?php
namespace Daedelus\Foundation\Console\Commands\Cache;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cache:set')]
class SetCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:set {key} {value} {group} {expiration}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sets a value to the object cache, regardless of whether it already exists.';

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

		$result = wp_cache_set( $key, $value, $group, $expiration );

		if ( false === $result ) {
			$this->error( "Could not add object '$key' in group '$group'." );
			return;
		}

		$this->line( "Set object '$key' in group '$group'." );
	}
}