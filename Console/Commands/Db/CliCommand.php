<?php
namespace Daedelus\Foundation\Console\Commands\Db;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'db:cli')]
class CliCommand extends Command
{
	/** @var string */
	protected $signature = 'db:cli';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}