<?php
namespace Daedelus\Framework\Console\Commands\Maintenance;

use Daedelus\Framework\Console\Commands\Concerns\ManageMaintenanceMode;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command;

/**
 *
 */
#[AsCommand(name: 'maintenance:disable')]
class DisableMaintenanceCommand extends Command
{
	use ManageMaintenanceMode;

	/** @var string */
	protected $signature = 'maintenance:disable
	                        {--force}';

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
		if ( !$this->getMaintenanceStatus() && !$this->hasOption('force') ) {
			$this->error('Maintenance mode already disabled.');
			return;
		}

		$this->setMaintenanceMode( false );

		$this->info('Disabled Maintenance mode.');
	}
}