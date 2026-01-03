<?php
namespace Daedelus\Foundation\Console\Commands\Option;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'option:delete')]
class DeleteCommand extends Command
{
	/** @var string */
	protected $signature = 'option:delete {keys?*}';

	/** @var string */
	protected $description = 'Deletes an option.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$keys = $this->argument('keys') ?? [];

		foreach ( $keys as $key ) {
			if ( ! delete_option( $key ) ) {
				$this->warn( "Could not delete '{$key}' option. Does it exist?" );
			} else {
				$this->info( "Deleted '{$key}' option." );
			}
		}
	}
}