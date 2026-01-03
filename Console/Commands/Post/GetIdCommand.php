<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:get-id')]
class GetIdCommand extends Command
{
	/** @var string */
	protected $signature = 'post:get-id';

	/** @var string */
	protected $description = 'Gets the post ID for a given URL.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}