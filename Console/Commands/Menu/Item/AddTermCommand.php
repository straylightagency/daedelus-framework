<?php
namespace Daedelus\Foundation\Console\Commands\Menu\Item;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:item:add-term')]
class AddTermCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:item:add-term';

	/** @var string */
	protected $description = 'Adds a taxonomy term as a menu item.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}