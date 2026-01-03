<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Daedelus\Foundation\Console\Commands\Concerns\ManageThemes;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:is-installed')]
class IsInstalledCommand extends Command
{
	use ManageThemes;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:is-installed {theme}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Checks if a given theme is installed.';

	/**
	 * @return int
	 */
	public function handle(): int
	{
		$arg = trim( $this->argument('theme' ) );

		$theme = $this->findOne( $arg );

		return $theme->exists() ? 0 : 1;
	}
}