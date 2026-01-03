<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;
use Daedelus\Support\Filters;

class DisableTrackbacks extends Hook
{
	/**
	 * @return void
	 */
    public function register():void
    {
	    Filters::add('xmlrpc_methods', fn (array $methods) => collect( $methods )->except( [ 'pingback.ping' ] )->toArray() );
	    Filters::add('wp_headers', fn (array $headers) => collect( $headers )->except( [ 'X-Pingback' ] )->toArray() );
	    Filters::add('bloginfo_url', fn (string $output, ?string $show = null) => $show === 'pingback_url' ? '' : $output, 10, 2 );

	    Filters::add('rewrite_rules_array', function (array $rules) {
			return collect( $rules )->filter( fn ($value, string $rule) => !preg_match('/trackback\/\?\$$/i', $rule ) )->toArray();
	    } );

	    Actions::add('xmlrpc_call', function (string $action) {
		    if ( $action === 'pingback.ping' ) {
			    wp_die('Pingbacks are not supported', 'Not Allowed!', ['response' => 403 ] );
		    }
	    } );
    }
}