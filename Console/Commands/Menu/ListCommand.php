<?php
namespace Daedelus\Foundation\Console\Commands\Menu;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:list')]
class ListCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:list';

	/** @var string */
	protected $description = 'Gets a list of menus.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}