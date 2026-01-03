<?php
namespace Daedelus\Foundation\Console\Commands\Db;

use Daedelus\Foundation\Console\Commands\Concerns\CanRunMySqlCommand;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'db:check')]
class CheckCommand extends Command
{
	use CanRunMySqlCommand;

	/** @var string */
	protected $signature = 'db:check';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}