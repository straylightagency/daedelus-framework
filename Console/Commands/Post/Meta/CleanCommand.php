<?php
namespace Daedelus\Foundation\Console\Commands\Post\Meta;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:meta:clean')]
class CleanCommand extends Command
{
	/** @var string */
	protected $signature = 'post:meta:clean';

	/** @var string */
	protected $description = 'Cleans up duplicate post meta values on a post.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}