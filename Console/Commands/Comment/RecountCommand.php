<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:recount')]
class RecountCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:recount';

	/** @var string */
	protected $description = 'Recalculates the comment_count value for one or more posts.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}