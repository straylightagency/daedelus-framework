<?php
namespace Daedelus\Foundation\Console\Commands\User;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'user:get')]
class GetCommand extends Command
{
	/** @var string */
	protected $signature = 'user:get';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}