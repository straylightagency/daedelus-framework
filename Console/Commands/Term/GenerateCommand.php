<?php
namespace Daedelus\Foundation\Console\Commands\Term;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'term:generate')]
class GenerateCommand extends Command
{
	/** @var string */
	protected $signature = 'term:generate';

	/** @var string */
	protected $description = '';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}