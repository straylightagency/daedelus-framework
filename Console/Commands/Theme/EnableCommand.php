<?php
namespace Daedelus\Foundation\Console\Commands\Theme;

use Daedelus\Foundation\Console\Commands\Concerns\ManageThemes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'theme:enable')]
class EnableCommand extends Command
{
	use ManageThemes;

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'theme:enable {theme} {--network} {--activate}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Enables a theme on a WordPress multisite install.';

	/**
	 * @return void
	 */
	public function handle(): void
	{
		$arg = trim( $this->argument('theme' ) );
		$network = $this->hasOption('network') && $this->option('network');
		$activate = $this->hasOption('activate') && $this->option('activate');

		if ( $network && !is_multisite() ) {
			$this->error( 'This is not a multisite installation.' );
			return;
		}

		$theme = $this->findOne( $arg );
		$name  = $theme->get( 'Name' );

		# If the --network flag is set, we'll be calling the (get|update)_site_option functions
		$_site = $network ? '_site' : '';

		# Add the current theme to the allowed themes option or site option
		$allowed_themes = call_user_func( "get{$_site}_option", 'allowedthemes' );
		if ( empty( $allowed_themes ) ) {
			$allowed_themes = array();
		}
		$allowed_themes[ $theme->get_stylesheet() ] = true;
		call_user_func( "update{$_site}_option", 'allowedthemes', $allowed_themes );

		if ( $network ) {
			$this->components->success( "Network enabled the '$name' theme." );
		} else {
			$this->components->success( "Enabled the '$name' theme." );
		}

		# If the --activate flag is set, activate the theme for the current site
		if ( $activate ) {
			Artisan::call('theme:activate ' . $arg );
		}
	}
}