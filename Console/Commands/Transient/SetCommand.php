<?php
namespace Daedelus\Foundation\Console\Commands\Transient;

use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'transient:set')]
class SetCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'transient:set {key} {value} {expiration?} {--network}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sets a transient value.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$key = $this->argument('key');
		$value = $this->argument('value');
		$expiration = $this->argument('expiration') ?? 0;
		$network = $this->hasOption('network') && $this->option('network');

		$func = $network ? 'set_site_transient' : 'set_transient';

		if ( $func( $key, $value, $expiration ) ) {
			$this->info( 'Transient added.' );
		} else {
			$this->error( 'Transient could not be set.' );
		}
	}
}