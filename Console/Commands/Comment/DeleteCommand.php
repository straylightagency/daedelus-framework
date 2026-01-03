<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:delete')]
class DeleteCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:delete';

	/** @var string */
	protected $description = 'Deletes a comment.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}