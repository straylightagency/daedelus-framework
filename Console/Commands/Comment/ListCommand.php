<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:list')]
class ListCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:list';

	/** @var string */
	protected $description = 'Gets a list of comments.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}