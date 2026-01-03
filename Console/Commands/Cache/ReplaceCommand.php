<?php
namespace Daedelus\Foundation\Console\Commands\Cache;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'cache:replace')]
class ReplaceCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cache:replace {key} {value} {group} {expiration}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Replaces a value in the object cache, if the value already exists.';

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function handle(): void
	{
		$key = trim( $this->argument('key' ) );
		$value = $this->argument('value' );
		$group = trim( $this->argument('group' ) );
		$expiration = intval( $this->argument('expiration' ) );

		$result = wp_cache_replace( $key, $value, $group, $expiration );

		if ( false === $result ) {
			$this->error( "Could not replace object '$key' in group '$group'. Does it not exist?" );
			return;
		}

		$this->line( "Replaced object '$key' in group '$group'." );
	}
}