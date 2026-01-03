<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:unspam')]
class UnspamCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:unspam';

	/** @var string */
	protected $description = 'Unmarks a comment as spam.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}