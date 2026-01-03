<?php
namespace Daedelus\Foundation\Console\Commands\Transient;

use Illuminate\Console\Command;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'transient:type')]
class TypeCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'transient:type';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Determines the type of transients implementation.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		if ( wp_using_ext_object_cache() ) {
			$message = 'Transients are saved to the object cache.';
		} else {
			$message = 'Transients are saved to the database.';
		}

		$this->info( $message );
	}
}