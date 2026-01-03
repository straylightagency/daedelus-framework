<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;

class JsToFooter extends Hook
{
	/**
	 * @return void
	 */
    public function register():void
    {
		Actions::add( 'wp_enqueue_scripts', function () {
			Actions::remove('wp_head', 'wp_print_scripts');
			Actions::remove('wp_head', 'wp_print_head_scripts', 9);
			Actions::remove('wp_head', 'wp_enqueue_scripts', 1);
		} );
    }
}