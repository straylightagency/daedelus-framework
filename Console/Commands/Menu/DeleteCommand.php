<?php
namespace Daedelus\Foundation\Console\Commands\Menu;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:delete')]
class DeleteCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:delete';

	/** @var string */
	protected $description = 'Deletes one or more menus.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}