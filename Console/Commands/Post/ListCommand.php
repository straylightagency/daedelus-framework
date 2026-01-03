<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:list')]
class ListCommand extends Command
{
	/** @var string */
	protected $signature = 'post:list';

	/** @var string */
	protected $description = 'Gets a list of posts.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}