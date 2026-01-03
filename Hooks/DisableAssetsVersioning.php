<?php
namespace Daedelus\Foundation\Hooks;

use Closure;
use Daedelus\Support\Filters;

class DisableAssetsVersioning extends Hook
{
	/**
	 * @return void
	 */
    public function register():void
    {
        $remove_query_var = fn (string $url) => $url ? esc_url( remove_query_arg( 'ver', $url ) ) : false;

		Filters::add( 'script_loader_src', $remove_query_var, 15 );
		Filters::add( 'style_loader_src', $remove_query_var, 15 );
    }
}