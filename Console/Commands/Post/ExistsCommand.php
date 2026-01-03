<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:exists')]
class ExistsCommand extends Command
{
	/** @var string */
	protected $signature = 'post:exists';

	/** @var string */
	protected $description = 'Verifies whether a post exists.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}