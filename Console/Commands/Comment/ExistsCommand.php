<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:exists')]
class ExistsCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:exists';

	/** @var string */
	protected $description = 'Verifies whether a comment exists.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}