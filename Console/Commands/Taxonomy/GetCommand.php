<?php
namespace Daedelus\Foundation\Console\Commands\Taxonomy;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'taxonomy:get')]
class GetCommand extends Command
{
	/** @var string */
	protected $signature = 'taxonomy:get';

	/** @var string */
	protected $description = 'Gets details about a registered taxonomy.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}