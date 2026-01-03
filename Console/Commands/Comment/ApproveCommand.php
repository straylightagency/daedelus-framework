<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:approve')]
class ApproveCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:approve';

	/** @var string */
	protected $description = 'Approves a comment.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}