<?php
namespace Daedelus\Foundation\Console\Commands\User;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'user:check-password')]
class CheckPasswordCommand extends Command
{
	/** @var string */
	protected $signature = 'user:check-password';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}