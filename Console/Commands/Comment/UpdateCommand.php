<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:update')]
class UpdateCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:update';

	/** @var string */
	protected $description = 'Updates one or more comments.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}