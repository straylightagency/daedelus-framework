<?php
namespace Daedelus\Foundation\Console\Commands\Wp;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Install WordPress
 */
#[AsCommand(name: 'wp:install')]
class InstallCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'wp:install';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Install WordPress';

	/**
	 * @return void
	 */
	public function handle():void
	{
        $this->call('migrate', [
            '--path' => 'workbench/majestic/src/Daedelus/Foundation/Database/migrations',
        ]);
	}
}