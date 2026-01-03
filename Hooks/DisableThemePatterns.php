<?php

namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;

/**
 *
 */
class DisableThemePatterns extends Hook
{
	/**
	 * @return void
	 */
	public function register(): void
	{
		Actions::add( 'admin_init', function () {
            remove_submenu_page( 'themes.php', 'site-editor.php?p=/pattern' );
            remove_submenu_page( 'themes.php', 'edit.php?post_type=wp_block' );
		}, 100 );
	}
}