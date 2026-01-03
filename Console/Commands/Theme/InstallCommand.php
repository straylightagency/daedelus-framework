<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:install')]
class InstallCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:install {args?*} {--version=} {--force} {--activate} {--activate-network} {--insecure}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Installs one or more plugins.';

	/**
	 * @return void
	 */
	public function handle(): void
	{

	}
}