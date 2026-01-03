<?php
namespace Daedelus\Foundation\Console\Commands\User;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'user:create')]
class CreateCommand extends Command
{
	/** @var string */
	protected $signature = 'user:create';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}