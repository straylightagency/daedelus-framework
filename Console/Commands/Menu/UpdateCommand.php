<?php
namespace Daedelus\Foundation\Console\Commands\Menu;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'menu:update')]
class UpdateCommand extends Command
{
	/** @var string */
	protected $signature = 'menu:update';

	/** @var string */
	protected $description = 'Updates an existing menu.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}