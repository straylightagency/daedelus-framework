<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:trash')]
class TrashCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:trash';

	/** @var string */
	protected $description = 'Trashes a comment.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}