<?php

namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;
use Illuminate\Support\Str;

/**
 *
 */
class SetupApplication extends Hook
{
	/**
	 * @return void
	 */
	public function register(): void
	{
        /**
         * Sync WordPress (xx_XX) lang with Laravel
         */
        app()->setLocale( Str::before( determine_locale(), '_' ) );

		Actions::add('after_setup_theme', function () {
			app()->setThemePath( get_theme_file_path() );
			app()->setThemeUrl( get_theme_file_uri() );
		} );
	}
}