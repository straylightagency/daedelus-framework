<?php

namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;
use Daedelus\Support\Filters;

/**
 *
 */
class CustomizeLogin extends Hook
{
	/**
	 * @return void
	 */
	public function register(): void
	{
		/**
		 * Change the WordPress login header to the blog name
		 *
		 * @return string
		 */
		Filters::add('login_headertext', fn () => get_bloginfo('name') );

		/**
		 * Change the WordPress login header URL to the home URL
		 *
		 * @return string
		 */
		Filters::add('login_headerurl', fn () => home_url() );

        /**
         * Replace the WordPress login logo to the creator logo
         *
         * @return string
         */
        Actions::add('login_enqueue_scripts', function () {
            echo '<style type="text/css">
    #login h1 a, .login h1 a {
        background-image: url(' . get_stylesheet_directory_uri() . '/logo.svg);
        height: 100px; /* Change the height as needed */
        width: 100%; /* Use 100% width for responsiveness */
        background-size: contain; /* Adjust this property as needed */
    }
</style>';
        } );
	}
}