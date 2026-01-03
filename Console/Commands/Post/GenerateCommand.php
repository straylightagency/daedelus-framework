<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:generate')]
class GenerateCommand extends Command
{
	/** @var string */
	protected $signature = 'post:generate';

	/** @var string */
	protected $description = 'Generates some posts.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}