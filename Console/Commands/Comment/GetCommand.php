<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:get')]
class GetCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:get';

	/** @var string */
	protected $description = 'Gets the data of a single comment.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}