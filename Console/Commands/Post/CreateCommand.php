<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:create')]
class CreateCommand extends Command
{
	/** @var string */
	protected $signature = 'post:create';

	/** @var string */
	protected $description = 'Creates a new post.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}