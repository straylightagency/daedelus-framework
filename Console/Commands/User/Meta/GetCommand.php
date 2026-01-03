<?php
namespace Daedelus\Foundation\Console\Commands\User\Meta;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'user:meta:get')]
class GetCommand extends Command
{
	/** @var string */
	protected $signature = 'user:meta:get';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}