<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:count')]
class CountCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:count';

	/** @var string */
	protected $description = 'Counts comments, on whole blog or on a given post.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}