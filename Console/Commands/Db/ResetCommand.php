<?php
namespace Daedelus\Foundation\Console\Commands\Db;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'db:reset')]
class ResetCommand extends Command
{
	/** @var string */
	protected $signature = 'db:reset';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}