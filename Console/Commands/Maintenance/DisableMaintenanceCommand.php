<?php
namespace Daedelus\Foundation\Console\Commands\Maintenance;

use Daedelus\Foundation\Console\Commands\Concerns\GetMaintenanceStatus;
use Daedelus\Foundation\Console\Commands\Concerns\ManageMaintenanceMode;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'maintenance:disable')]
class DisableMaintenanceCommand extends Command
{
	use GetMaintenanceStatus, ManageMaintenanceMode;

	/** @var string */
	protected $signature = 'maintenance:disable';

	/** @var string */
	protected $description = 'Disabling the maintenance mode.';

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
		if ( $this->getStatus() && !$this->hasOption('force') ) {
			$this->error('Maintenance mode already disabled.');
			return;
		}

		$this->setMaintenanceMode( false );

		$this->info('Disabled Maintenance mode.');
	}
}