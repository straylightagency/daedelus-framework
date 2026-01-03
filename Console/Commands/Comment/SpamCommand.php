<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:spam')]
class SpamCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:spam';

	/** @var string */
	protected $description = 'Marks a comment as spam.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}