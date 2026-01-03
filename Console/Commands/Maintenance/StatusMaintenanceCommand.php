<?php
namespace Daedelus\Foundation\Console\Commands\Maintenance;

use Daedelus\Foundation\Console\Commands\Concerns\GetMaintenanceStatus;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'maintenance:status')]
class StatusMaintenanceCommand extends Command
{
	use GetMaintenanceStatus;

	/** @var string */
	protected $signature = 'maintenance:status';

	/** @var string */
	protected $description = 'Get the maintenance mode current status.';

	/**
	 * @param Filesystem $files
	 */
	public function __construct(protected Filesystem $files)
	{
		parent::__construct();
	}

	/**
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function handle():void
	{
		$status = $this->getStatus() ? 'enabled' : 'disabled';

		$this->info("Maintenance mode is {$status}.");
	}
}