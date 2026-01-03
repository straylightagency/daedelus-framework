<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:update')]
class UpdateCommand extends Command
{
	/** @var string */
	protected $signature = 'post:update';

	/** @var string */
	protected $description = 'Updates one or more existing posts.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}