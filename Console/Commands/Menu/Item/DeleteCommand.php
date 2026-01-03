<?php
namespace Daedelus\Foundation\Console\Commands\Menu\Item;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:item:delete')]
class DeleteCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:item:delete';

	/** @var string */
	protected $description = 'Deletes one or more items from a menu.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}