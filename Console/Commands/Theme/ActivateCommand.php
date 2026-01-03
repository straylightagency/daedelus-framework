<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Daedelus\Foundation\Console\Commands\Concerns\ManageThemes;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:activate')]
class ActivateCommand extends Command
{
	use ManageThemes;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:activate {theme}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Activates a theme.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$arg = trim( $this->argument('theme' ) );

		$theme = $this->findOne( $arg );

		$errors = $theme->errors();

		if ( is_wp_error( $errors ) ) {
			$message = $errors->get_error_message();
			$this->error( $message );
		}

		$name = $theme->get( 'Name' );

		if ( 'active' === $this->getStatus( $theme ) ) {
			$this->warn( "The '$name' theme is already active." );
			return;
		}

		if ( $theme->get_stylesheet() !== $theme->get_template() && ! $this->findOne( $theme->get_template() ) ) {
			$this->error( "The '{$theme->get_stylesheet()}' theme cannot be activated without its parent, '{$theme->get_template()}'." );
		}

		switch_theme( $theme->get_template(), $theme->get_stylesheet() );

		if ( $this->isActiveTheme( $theme ) ) {
			$this->components->success( "Switched to '$name' theme." );
		} else {
			$this->error( "Could not switch to '$name' theme." );
		}
	}
}