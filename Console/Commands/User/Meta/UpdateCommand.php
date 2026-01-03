<?php
namespace Daedelus\Foundation\Console\Commands\User\Meta;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'user:meta:update')]
class UpdateCommand extends Command
{
	/** @var string */
	protected $signature = 'user:meta:update';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}