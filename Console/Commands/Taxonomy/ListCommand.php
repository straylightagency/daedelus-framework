<?php
namespace Daedelus\Foundation\Console\Commands\Taxonomy;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'taxonomy:list')]
class ListCommand extends Command
{
	/** @var string */
	protected $signature = 'taxonomy:list';

	/** @var string */
	protected $description = 'Lists registered taxonomies.';

	/**
	 * @return void
	 */
	public function handle():void
	{

	}
}