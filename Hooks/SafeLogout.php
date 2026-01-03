<?php

namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;

/**
 *
 */
class SafeLogout extends Hook
{
	/**
	 * @return void
	 */
	public function register(): void
	{
		Actions::add( 'wp_logout', function () {
			if ( wp_redirect( home_url() ) ) {
				exit();
			}
		} );
	}
}