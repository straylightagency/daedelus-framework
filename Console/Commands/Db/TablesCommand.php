<?php
namespace Daedelus\Foundation\Console\Commands\Db;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'db:tables')]
class TablesCommand extends Command
{
	/** @var string */
	protected $signature = 'db:tables';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}