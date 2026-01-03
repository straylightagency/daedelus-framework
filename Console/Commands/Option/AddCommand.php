<?php
namespace Daedelus\Foundation\Console\Commands\Option;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'option:add')]
class AddCommand extends Command
{
	/** @var string */
	protected $signature = 'option:add {key} {value} {--autoload=} {--format=}';

	/** @var string */
	protected $description = 'Adds a new option value.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$key = $this->argument('key');
		$value = $this->argument('value');
		$autoload = $this->option('autoload');
		$format = $this->option('format') ?? 'plaintext';

		if ( in_array( $autoload, [ 'no', 'off' ], true ) ) {
			$autoload = 'no';
		} else {
			$autoload = 'yes';
		}

		if ( ! add_option( $key, $value, '', $autoload ) ) {
			$this->error( "Could not add option '{$key}'. Does it already exist?" );
		} else {
			$this->info( "Added '{$key}' option." );
		}
	}
}