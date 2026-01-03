<?php
namespace Daedelus\Foundation\Console\Commands\Menu\Item;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:item:count')]
class CountCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:item:count';

	/** @var string */
	protected $description = 'Count menu items.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}