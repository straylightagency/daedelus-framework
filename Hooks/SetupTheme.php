<?php

namespace Daedelus\Framework\Hooks;

use Daedelus\Support\Actions;
use Daedelus\Support\Filters;
use Illuminate\Support\Str;

/**
 *
 */
class SetupTheme extends Hook
{
	/**
	 * @return void
	 */
	public function register(): void
	{
		Actions::add('after_setup_theme', function () {
            /**
             * Sync WordPress (xx_XX) lang with Laravel
             */
            app()->setLocale( Str::before( determine_locale(), '_' ) );

            app()->setThemePath( get_theme_file_path() );
			app()->setThemeUri( get_theme_file_uri() );
		} );
	}
}