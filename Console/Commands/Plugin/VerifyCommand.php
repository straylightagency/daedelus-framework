<?php
namespace Daedelus\Foundation\Console\Commands\Plugin;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'plugin:verify')]
class VerifyCommand extends Command
{
	/** @var string */
	protected $signature = 'plugin:verify';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}