<?php

use App\Models\User;
use Daedelus\Foundation\Cache\WordPressProxyCache;
use Daedelus\Foundation\Mix;
use Daedelus\Foundation\Vite;
use Daedelus\Support\Filters;
use Daedelus\Theme\Menus\Menu;
use Daedelus\Theme\Menus\MenuManager;
use Daedelus\Theme\ViewMetadata;
use Daedelus\Theme\ViewOptions;
use Daedelus\Theme\ViewScanner;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Js;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

if ( !function_exists( 'app_url' ) ) {
	/**
	 * The URL of the app
	 *
	 * @param string $path
	 * @return string
	 */
	function app_url(string $path = ''):string
	{
		return config( 'app.url' ) . ( $path != '' ? '/' . $path : '' );
	}
}

if ( !function_exists( 'app_name' ) ) {
	/**
	 * The URL of the app
	 *
	 * @return string
	 */
	function app_name():string
	{
		return get_bloginfo( 'name' );
	}
}

if ( !function_exists( 'theme_path' ) ) {
	/**
	 * The path to the theme directory
	 *
	 * @param string $path
	 * @return string
	 */
	function theme_path(string $path = ''):string
	{
		return app()->themePath( $path );
	}
}

if ( !function_exists( 'theme_url' ) ) {
	/**
	 * The url to the theme directory
	 *
	 * @param string $url
	 * @return string
	 */
	function theme_url(string $url = ''):string
	{
		return app()->themeUrl( $url );
	}
}

if ( !function_exists( 'content_path' ) ) {
	/**
	 * The path to the content directory
	 *
	 * @param string $path
	 * @return string
	 */
	function content_path(string $path = ''):string
	{
		return app()->contentPath( $path );
	}
}

if ( !function_exists( 'public_content_url' ) ) {
	/**
	 * The URL to the content directory
	 *
	 * @param string $path
	 * @return string
	 */
	function public_content_url(string $path = ''):string
	{
		return app()->contentUrl( $path );
	}
}

if ( !function_exists( 'plugins_path' ) ) {
	/**
	 * The path to plugins
	 *
	 * @param string $path
	 * @return string
	 */
	function plugins_path(string $path = ''):string
	{
		return app()->pluginsPath( $path );
	}
}

if ( !function_exists( 'mu_plugins_path' ) ) {
	/**
	 * The path to must-use plugins
	 *
	 * @param string $path
	 * @return string
	 */
	function mu_plugins_path(string $path = ''):string
	{
		return app()->muPluginsPath( $path );
	}
}

if ( !function_exists( 'is_debug' ) ) {
	/**
	 * If debug mode is enabled
	 *
	 * @return bool
	 */
	function is_debug():bool
	{
		return config( 'app.debug', false );
	}
}

if ( !function_exists( 'render' ) ) {
	/**
	 * Define the render callback for the template
	 *
	 * @param Closure $callback
	 *
	 * @return ViewOptions
	 */
	function render(Closure $callback): ViewOptions
	{
		app( ViewScanner::class )->whenListening(
			fn () => ViewMetadata::instance()->renders[] = $callback
		);

		return new ViewOptions;
	}
}

if ( !function_exists('withFields') ) {
    /**
     * Define a default render callback for the template that pass the ACF fields to the view
     *
     * @param Closure|null $callback
     * @param string $key
     *
     * @return ViewOptions
     */
	function withFields(Closure|null $callback = null, string $key = 'fields'): ViewOptions
	{
        if ( $callback ) {
            fields( $callback );
        }

		return render( fn ( WP_post $post ) => [ $key => get_fields( $post->ID ) ] );
	}
}

if ( !function_exists( 'fields' ) ) {
    /**
     * Define the fields for the template
     *
     * @param Closure $callback
     *
     * @return ViewOptions
     */
    function fields(Closure $callback): ViewOptions
    {
        app( ViewScanner::class )->whenListening(
            fn () => ViewMetadata::instance()->fields = $callback
        );

        return new ViewOptions;
    }
}

if ( !function_exists('withPost') ) {
	/**
	 * Define a default render callback for the template that pass the WP_Post to the view
	 *
	 * @param string $key
	 *
	 * @return ViewOptions
	 */
	function withPost(string $key = 'post'): ViewOptions
	{
		return render( fn ( WP_post $post ) => [ $key => $post ] );
	}
}

if ( !function_exists( 'middleware' ) ) {
	/**
	 * Define the middleware of the template
	 *
	 * @param array $middleware
	 *
	 * @return ViewOptions
	 */
	function middleware(array $middleware): ViewOptions
	{
		app( ViewScanner::class )->whenListening(
			fn () => ViewMetadata::instance()->middleware = $middleware
		);

		return new ViewOptions;
	}
}

if ( !function_exists( 'name' ) ) {
	/**
	 * Define the name of the template
	 *
	 * @param string $name
	 *
	 * @return ViewOptions
	 */
	function name(string $name): ViewOptions
	{
		app( ViewScanner::class )->whenListening(
			fn () => ViewMetadata::instance()->name = $name
		);

		return new ViewOptions;
	}
}

if ( !function_exists( 'type' ) ) {
	/**
	 * Define the post type for the template
	 *
	 * @param string $type
	 *
	 * @return ViewOptions
	 */
	function type(string $type): ViewOptions
	{
		app( ViewScanner::class )->whenListening(
			fn () => ViewMetadata::instance()->type = $type
		);

		return new ViewOptions;
	}
}

if ( !function_exists('page_title') ) {
	/**
	 * Define the page title during rendering
	 *
	 * @param string $page_title
	 *
	 * @return void
	 */
	function page_title(string $page_title): void
	{
		app( ViewScanner::class )->whenRendering(
			function () use ( $page_title ) {
				Filters::add('document_title_parts', function ($title) use ($page_title) {
					$title['title'] = $page_title;

					return $title;
				} );
			}
		);
	}
}

if ( !function_exists( 'abort_404' ) ) {
	/**
	 * Tell WordPress and Laravel to handle a 404
	 *
	 * @return void
	 */
	function abort_404():void
	{
		global $wp_query;
		$wp_query->set_404();

		throw new NotFoundHttpException();
	}
}

if ( !function_exists( 'vite' ) ) {
	/**
	 * Process Vite on assets entries
	 *
	 * @param array|string $entries
	 *
	 * @return string
	 * @throws Exception
	 */
	function vite(array|string $entries): string
	{
		return app( Vite::class )->asset( $entries );
	}
}

if ( !function_exists( 'mix' ) ) {
	/**
	 * Process Mix on assets entries
	 *
	 * @param array|string $entries
	 *
	 * @return string
	 * @throws Exception
	 */
	function mix(array|string $entries): string
	{
		return app( Mix::class )->asset( $entries );
	}
}

if ( !function_exists( 'option' ) ) {
	/**
	 * @param string $field
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	function option(string $field, mixed $default = null):mixed
	{
        $keys = [];

        if ( str_contains( $field, '.' ) ) {
            $keys = explode('.', $field );
            $field = array_shift( $keys );
        }

		if ( $value = get_field( $field, 'option') ) {
            if ( !empty( $keys ) ) {
                foreach ( $keys as $key ) {
                    $value = $value[ $key ] ?? null;
                }
            }

			return $value;
		}

		return $default;
	}
}

if ( !function_exists( 'wp_cache' ) ) {
	/**
	 * @return WordPressProxyCache
	 */
	function wp_cache():WordPressProxyCache
	{
		return app( WordPressProxyCache::class );
	}
}

if ( !function_exists( 'menu' ) ) {
	/**
	 * @param string|null $menu_name
	 *
	 * @return MenuManager|Menu
	 * @throws \Daedelus\Theme\Menus\Exceptions\MenuNotFoundException
	 */
	function menu(?string $menu_name):MenuManager|Menu
	{
		/** @var MenuManager $manager */
		$manager = app( MenuManager::class );

		if ( $menu_name ) {
			return $manager->get( $menu_name );
		}

		return $manager;
	}
}

if ( !function_exists( 'get_home_page' ) ) {
	/**
	 * @param bool $with_fields
	 * @return WP_Post
	 */
	function get_home_page(bool $with_fields = false): WP_Post
	{
		$page = get_post( get_option('page_on_front') );

		if ( $with_fields ) {
			$page->fields = get_fields( $page->ID );
		}

		return $page;
	}
}

if ( !function_exists('get_admin_post_url') ) {
    /**
     * Return the admin-post.php url
     *
     * @return string
     */
    function get_admin_post_url(): string
    {
        return admin_url( 'admin-post.php' );
    }
}

if ( !function_exists( 'is_local' ) ) {
	/**
	 * Return if the app is in local mode or not
	 *
	 * @return bool
	 */
	function is_local(): bool
	{
		return app()->environment('local');
	}
}

if ( !function_exists( 'is_staging' ) ) {
	/**
	 * Return if the app is in staging mode or not
	 *
	 * @return bool
	 */
	function is_staging(): bool
	{
		return app()->environment('staging');
	}
}

if ( !function_exists( 'is_production' ) ) {
	/**
	 * Return if the app is in production mode or not
	 *
	 * @return bool
	 */
	function is_production(): bool
	{
		return app()->environment('production');
	}
}

if ( !function_exists( 'export_routes' ) ) {
	/**
	 * Export application routes url by name
	 *
	 * @param array $which
	 * @return array
	 */
	function export_routes(array $which = []): array
	{
		$routes = collect( app('router')->getRoutes()->getRoutesByName() );

		if ( !empty( $which ) ) {
			$routes = $routes->only( $which );
		}

		$routes = $routes->mapWithKeys( function ( Route $value, $key ) {
			return [ $key => $value->uri() ];
		} );

		return $routes->toArray();
	}
}

if ( !function_exists('carbon') ) {
    /**
     * Create a new Carbon date from string
     *
     * @param string $date
     * @return Carbon
     */
    function carbon(string $date): Carbon
    {
        return new Carbon( $date );
    }
}

if ( !function_exists('json_from') ) {
	/**
	 * Transform an array or an object into JSON
	 *
	 * @param $data
	 * @return Js
	 * @throws JsonException
	 */
	function json_from($data): Js
	{
		return Js::from( $data );
	}
}

if ( !function_exists('force_logout') ) {
    /**
     * Force the user to log out
     *
     * @param Request $request
     * @param string $guard
     * @return void
     */
    function force_logout(Request $request, string $guard = 'web'): void
    {
        auth( $guard )->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
    }
}

if ( !function_exists( 'user' ) ) {
    /**
     * Return the user
     *
     * @param string $guard
     * @return User|null
     */
    function user(string $guard = 'web'): User|null
    {
        /** @var User $user */
        $user = auth( $guard )->user();

        return $user;
    }
}