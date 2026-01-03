<?php
namespace Daedelus\Foundation\Console\Commands\User;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'user:add-cap')]
class AddCapCommand extends Command
{
	/** @var string */
	protected $signature = 'user:add-cap';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}