<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:search')]
class SearchCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:search';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Displays plugins in the WordPress.org plugin directory matching a given search query.';

	/**
	 * @return void
	 */
	public function handle(): void
	{

	}
}