<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:trash')]
class TrashCommand extends Command
{
	/** @var string */
	protected $signature = 'post:trash';

	/** @var string */
	protected $description = 'Put a post into the trash.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}