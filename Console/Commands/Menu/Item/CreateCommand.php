<?php
namespace Daedelus\Foundation\Console\Commands\Menu\Item;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:item:create')]
class CreateCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:item:create';

	/** @var string */
	protected $description = 'Create a new menu item.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}