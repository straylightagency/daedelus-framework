<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:delete')]
class DeleteCommand extends Command
{
	/** @var string */
	protected $signature = 'post:delete';

	/** @var string */
	protected $description = 'Deletes an existing post.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}