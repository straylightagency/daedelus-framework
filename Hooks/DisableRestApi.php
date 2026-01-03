<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;
use Daedelus\Support\Filters;
use WP_Error;

class DisableRestApi extends Hook
{
	/**
	 * @return void
	 */
    public function register():void
    {
	    Actions::remove('xmlrpc_rsd_apis', 'rest_output_rsd');
	    Actions::remove('template_redirect', 'rest_output_link_header', 11 );
	    Actions::remove('wp_head', 'rest_output_link_wp_head', 10 );

		Filters::add('rest_authentication_errors',
			function ( $result ) {
				if ( !is_user_logged_in() ) {
					return new WP_Error( 'rest_forbidden', 'REST API forbidden.',
						['status' => rest_authorization_required_code() ]
					);
				}

				return $result;
			}, 15 );
    }
}