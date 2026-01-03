<?php
namespace Daedelus\Foundation\Console\Commands\Menu;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:create')]
class CreateCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:create';

	/** @var string */
	protected $description = 'Creates a new menu.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}