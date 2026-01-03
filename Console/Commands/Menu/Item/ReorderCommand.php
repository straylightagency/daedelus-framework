<?php
namespace Daedelus\Foundation\Console\Commands\Menu\Item;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:item:reorder')]
class ReorderCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:item:reorder';

	/** @var string */
	protected $description = 'Move block of items in one nav_menu up or down by incrementing/decrementing their menu_order field.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}