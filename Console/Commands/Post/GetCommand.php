<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:get')]
class GetCommand extends Command
{
	/** @var string */
	protected $signature = 'post:get';

	/** @var string */
	protected $description = 'Gets details about a post.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}