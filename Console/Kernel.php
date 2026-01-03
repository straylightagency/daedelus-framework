<?php

namespace Daedelus\Foundation\Console;

use Daedelus\Foundation\Bootstrap\BootWordPress;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Daedelus\Foundation\Bootstrap\HandleExceptions;
use Daedelus\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Daedelus\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use Illuminate\Foundation\Console\Kernel as BaseKernel;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 *
 */
class Kernel extends BaseKernel
{
	/**
	 * The bootstrap classes for the application.
	 *
	 * @var string[]
	 */
	protected $bootstrappers = [
		LoadEnvironmentVariables::class,
		LoadConfiguration::class,
		HandleExceptions::class,
		RegisterFacades::class,
		SetRequestForConsole::class,
		RegisterProviders::class,
		BootProviders::class,
//		BootWordPress::class
	];

	/**
	 * The paths where Artisan commands should be automatically discovered.
	 *
	 * @var array
	 */
	protected array $daedelusCommandPaths = [
		__DIR__ . '/Commands/Cache',
		__DIR__ . '/Commands/Comment',
		__DIR__ . '/Commands/Cron',
		__DIR__ . '/Commands/Db',
		__DIR__ . '/Commands/Maintenance',
		__DIR__ . '/Commands/Option',
		__DIR__ . '/Commands/Plugin',
		__DIR__ . '/Commands/Post',
		__DIR__ . '/Commands/Taxonomy',
		__DIR__ . '/Commands/Term',
		__DIR__ . '/Commands/Theme',
		__DIR__ . '/Commands/Transient',
		__DIR__ . '/Commands/User',
		__DIR__ . '/Commands/Wp',
	];

	/**
	 * Define the application's command schedule.
	 */
	protected function schedule(Schedule $schedule): void
	{
	}

	/**
	 * Register the commands for the application.
	 * @throws \ReflectionException
	 */
	protected function commands(): void
	{
		$this->loadDaedelusCommands();

		require base_path('routes/console.php');
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 */
	protected function loadDaedelusCommands(): void
	{
		$namespace = __NAMESPACE__;

		foreach ( Finder::create()->in( $this->daedelusCommandPaths )->files() as $file ) {
			$command = $namespace . str_replace(
					['/', '.php'],
					['\\', ''],
					Str::after( $file->getRealPath(), realpath( __DIR__ ) )
				);

			if ( is_subclass_of($command, Command::class) && !( new ReflectionClass( $command ) )->isAbstract() ) {
				Artisan::starting( function ( $artisan ) use ( $command ) {
					$artisan->resolve( $command );
				} );
			}
		}
	}
}