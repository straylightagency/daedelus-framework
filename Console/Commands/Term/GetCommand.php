<?php
namespace Daedelus\Foundation\Console\Commands\Term;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'term:get')]
class GetCommand extends Command
{
	/** @var string */
	protected $signature = 'term:get';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}