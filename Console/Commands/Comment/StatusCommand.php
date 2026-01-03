<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:status')]
class StatusCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:status';

	/** @var string */
	protected $description = 'Gets the status of a comment.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}