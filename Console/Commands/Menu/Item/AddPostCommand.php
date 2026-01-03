<?php
namespace Daedelus\Foundation\Console\Commands\Menu\Item;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:item:add-post')]
class AddPostCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:item:add-post';

	/** @var string */
	protected $description = 'Adds a post as a menu item.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}