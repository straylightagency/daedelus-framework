<?php
namespace Daedelus\Foundation\Console\Commands\Menu\Item;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:item:update')]
class UpdateCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:item:update';

	/** @var string */
	protected $description = 'Updates a menu item.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}