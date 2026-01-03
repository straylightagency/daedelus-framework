<?php
namespace Daedelus\Foundation\Console\Commands\Post;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'post:edit')]
class EditCommand extends Command
{
	/** @var string */
	protected $signature = 'post:edit';

	/** @var string */
	protected $description = 'Launches system editor to edit post content.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}