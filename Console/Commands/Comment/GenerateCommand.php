<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:generate')]
class GenerateCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:generate';

	/** @var string */
	protected $description = 'Generates some number of new dummy comments.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}