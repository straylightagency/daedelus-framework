<?php
namespace Daedelus\Foundation\Console\Commands\Db;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'db:search-replace')]
class SearchReplaceCommand extends Command
{
	/** @var string */
	protected $signature = 'db:search-replace';

	/** @var string */
	protected $description = 'Searches/replaces strings in the database.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}