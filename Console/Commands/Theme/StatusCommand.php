<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Daedelus\Foundation\Console\Commands\Concerns\ManageThemes;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:status')]
class StatusCommand extends Command
{
	use ManageThemes;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:status {theme}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Reveals the status of one themes.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$theme = trim( $this->argument('theme' ) );

		$theme = $this->findOne( $theme );

		$errors = $theme->errors();

		if ( is_wp_error( $errors ) ) {
			$message = $errors->get_error_message();

			$this->error( $message );
		}
	}
}