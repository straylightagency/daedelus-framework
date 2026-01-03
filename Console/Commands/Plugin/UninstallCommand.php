<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:uninstall')]
class UninstallCommand extends Command
{
	/** @var string */
	protected $signature = 'plugin:uninstall';

	/** @var string */
	protected $description = 'Uninstalls one or more plugins.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$args = (array) $this->argument('plugins') ?? [];
		$all = $this->hasOption('all') && $this->option('all');
		$dry_run = $this->hasOption('dry-run') && $this->option('dry-run');
		$exclude = $this->option('exclude') ?? [];
	}
}