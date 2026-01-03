<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:count')]
class CountCommand extends Command
{
	/** @var string */
	protected $signature = 'post:count';

	/** @var string */
	protected $description = 'Counts posts.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}