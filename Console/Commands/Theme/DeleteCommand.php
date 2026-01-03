<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Daedelus\Foundation\Console\Commands\Concerns\ManageThemes;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:delete')]
class DeleteCommand extends Command
{
	use ManageThemes;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:delete {themes?*} {--all}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Deletes one or more themes from the filesystem.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$args = $this->argument('themes');

		$all = $this->hasOption( 'all' ) && $this->option( 'all' );

		if ( empty( $args ) && !$all ) {
			$this->error( 'Please specify one or more themes, or use --all.' );
			return;
		}

		if ( $all ) {
			$themes = $this->findAll();
		} else {
			$themes = $this->findMany( $args );
		}

		$force = $this->hasOption( 'force' ) && $this->option( 'force' );

		$successes = 0;
		$errors = 0;

		foreach ( $themes as $theme ) {
			$theme_slug = $theme->get_stylesheet();

			if ( $this->isActiveTheme( $theme ) && !$force ) {
				if ( !$all ) {
					$this->warn( "Can't delete the currently active theme: $theme_slug" );
					++$errors;
				}

				continue;
			}

			if ( $this->isActiveParentTheme( $theme ) && !$force ) {
				if ( !$all ) {
					$this->warn( "Can't delete the parent of the currently active theme: $theme_slug" );
					++$errors;
				}

				continue;
			}

			$r = delete_theme( $theme_slug );

			if ( is_wp_error( $r ) ) {
				$this->warn( $r );
				++$errors;
			} else {
				$this->info( "Deleted '$theme_slug' theme." );
				++$successes;
			}
		}
	}
}