<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:untrash')]
class UntrashCommand extends Command
{
	/** @var string */
	protected $signature = 'post:untrash';

	/** @var string */
	protected $description = 'Remove a post from the trash.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}