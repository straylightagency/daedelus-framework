<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;

class NiceSearch extends Hook
{
	/**
	 * @return void
	 */
    public function register():void
    {
		Actions::add('template_redirect', function () {
			global $wp_rewrite;

			if ( !isset( $_SERVER['REQUEST_URI'] ) || !isset( $wp_rewrite ) || !is_object( $wp_rewrite ) || !$wp_rewrite->get_search_permastruct() ) {
				return;
			}

			$request = wp_unslash( filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL ) );

			$search_base = $wp_rewrite->search_base;

			if ( is_search()
				&& ! str_contains( $request, "/{$search_base}/" )
				&& ! str_contains( $request, '&' )
			) {
				wp_safe_redirect( get_search_link() );
				exit;
			}
		} );
    }
}