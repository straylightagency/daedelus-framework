<?php
namespace Daedelus\Foundation\Console\Commands\Menu\Item;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:item:list')]
class ListCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:item:list';

	/** @var string */
	protected $description = 'Gets a list of items associated with a menu.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}