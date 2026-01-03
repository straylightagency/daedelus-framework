<?php
namespace Daedelus\Foundation\Console\Commands\User;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'user:unspam')]
class UnspamCommand extends Command
{
	/** @var string */
	protected $signature = 'user:unspam';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}