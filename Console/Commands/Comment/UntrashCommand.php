<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:untrash')]
class UntrashCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:untrash';

	/** @var string */
	protected $description = 'Untrashes a comment.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}