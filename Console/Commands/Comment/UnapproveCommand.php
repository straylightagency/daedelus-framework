<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:unapprove')]
class UnapproveCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:unapprove';

	/** @var string */
	protected $description = 'Unapproves a comment.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}