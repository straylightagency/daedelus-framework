<?php

use App\Models\User;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Daedelus\Framework\Mix;
use Daedelus\Framework\Vite;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Daedelus\Support\Filters;
use Illuminate\Support\Carbon;
use Daedelus\Theme\Menus\Menu;
use Daedelus\Theme\ViewOptions;
use Daedelus\Theme\ViewScanner;
use Daedelus\Theme\ViewMetadata;
use Daedelus\Theme\Menus\MenuManager;
use Daedelus\Framework\Cache\WordPressProxyCache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

if ( ! function_exists( 'app_url' ) ) {
    /**
     * The URL of the app.
     *
     * @param string $path
     * @return string
     */
    function app_url(string $path = ''):string
    {
        return config( 'app.url' ) . ( $path != '' ? Str::start( $path, '/' ) : '' );
    }
}

if ( ! function_exists('app_name') ) {
    /**
     * Get the app name from WP options
     *
     * @return string
     */
    function app_name():string
    {
        return get_bloginfo( 'name', 'raw' );
    }
}

if ( ! function_exists( 'theme_path' ) ) {
    /**
     * The path to the theme directory.
     *
     * @param string $path
     * @return string
     */
    function theme_path(string $path = ''):string
    {
        return app()->themePath( $path );
    }
}

if ( ! function_exists( 'theme_url' ) ) {
    /**
     * The URL to the theme directory.
     *
     * @param string $url
     * @return string
     */
    function theme_url(string $url = ''):string
    {
        return app()->themeUrl( $url );
    }
}

if ( ! function_exists( 'content_path' ) ) {
    /**
     * The path to the content directory.
     *
     * @param string $path
     * @return string
     */
    function content_path(string $path = ''):string
    {
        return app()->contentPath( $path );
    }
}

if ( ! function_exists( 'public_content_url' ) ) {
    /**
     * The URL to the content directory.
     *
     * @param string $path
     * @return string
     */
    function public_content_url(string $path = ''):string
    {
        return app()->contentUrl( $path );
    }
}

if ( ! function_exists( 'plugins_path' ) ) {
    /**
     * The path to plugins.
     *
     * @param string $path
     * @return string
     */
    function plugins_path(string $path = ''):string
    {
        return app()->pluginsPath( $path );
    }
}

if ( ! function_exists( 'mu_plugins_path' ) ) {
    /**
     * The path to must-use plugins.
     *
     * @param string $path
     * @return string
     */
    function mu_plugins_path(string $path = ''):string
    {
        return app()->muPluginsPath( $path );
    }
}

if ( ! function_exists('upload_url') ) {
    /**
     * The URL to the upload directory
     *
     * @param string $path
     * @return string
     */
    function upload_url(string $path = ''):string
    {
        return app_url( UPLOADS ) . ( $path != '' ? Str::start( $path, '/' ) : '' );
    }
}

if ( ! function_exists('upload_path') ) {
    /**
     * The path to the upload directory
     *
     * @param string $path
     * @return string
     */
    function upload_path(string $path = ''):string
    {
        return app()->uploadPath( $path );
    }
}

if ( ! function_exists( 'is_debug' ) ) {
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

if ( ! function_exists( 'render' ) ) {
    /**
     * Define the render callback for the template.
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

if ( ! function_exists('withFields') ) {
    /**
     * Define a default render callback for the template that pass the ACF fields to the view.
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

if ( ! function_exists( 'fields' ) ) {
    /**
     * Define the fields for the template.
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

if ( ! function_exists('withPost') ) {
    /**
     * Define a default render callback for the template that pass the WP_Post to the view.
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

if ( ! function_exists( 'middleware' ) ) {
    /**
     * Define the middleware of the template.
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

if ( ! function_exists( 'name' ) ) {
    /**
     * Define the name of the template.
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

if ( ! function_exists( 'type' ) ) {
    /**
     * Define the post type for the template.
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

if ( ! function_exists('page_title') ) {
    /**
     * Define the page title during rendering.
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

if ( ! function_exists( 'abort_404' ) ) {
    /**
     * Tell both WordPress and Laravel to handle a 404.
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

if ( ! function_exists( 'vite' ) ) {
    /**
     * Process Vite on assets entries.
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

if ( ! function_exists( 'mix' ) ) {
    /**
     * Process Mix on assets entries.
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

if ( ! function_exists( 'option' ) ) {
    /**
     * Get an option field.
     *
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

if ( ! function_exists( 'wp_cache' ) ) {
    /**
     * Return the Proxy Cache.
     *
     * @return WordPressProxyCache
     */
    function wp_cache():WordPressProxyCache
    {
        return app( WordPressProxyCache::class );
    }
}

if ( ! function_exists( 'menu' ) ) {
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

if ( ! function_exists( 'get_home_page' ) ) {
    /**
     * Get the home page WP_Post.
     *
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

if ( ! function_exists('get_admin_post_url') ) {
    /**
     * Return the admin-post.php url.
     *
     * @return string
     */
    function get_admin_post_url(): string
    {
        return admin_url( 'admin-post.php' );
    }
}

if ( ! function_exists( 'is_local' ) ) {
    /**
     * Return if the app is in local mode or not.
     *
     * @return bool
     */
    function is_local(): bool
    {
        return app()->environment('local');
    }
}

if ( ! function_exists( 'is_staging' ) ) {
    /**
     * Return if the app is in staging mode or not.
     *
     * @return bool
     */
    function is_staging(): bool
    {
        return app()->environment('staging');
    }
}

if ( ! function_exists( 'is_production' ) ) {
    /**
     * Return if the app is in production mode or not.
     *
     * @return bool
     */
    function is_production(): bool
    {
        return app()->environment('production');
    }
}

if ( ! function_exists( 'export_routes' ) ) {
    /**
     * Export application routes url by name.
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

if ( ! function_exists('carbon') ) {
    /**
     * Create a new Carbon date from string.
     *
     * @param string $date
     * @return Carbon
     */
    function carbon(string $date): Carbon
    {
        return new Carbon( $date );
    }
}

if ( ! function_exists('date_locale') ) {
    /**
     * Format a given DateTime object into the configured app locale.
     *
     * @param DateTime $dateTime
     * @param string $format
     * @return string
     */
    function date_locale(DateTime $dateTime, string $format = 'eeee d LLLL yyyy'): string
    {
        $formatter = new IntlDateFormatter(
            app()->getLocale(),
            IntlDateFormatter::GREGORIAN,
            IntlDateFormatter::NONE
        );

        return tap( $formatter, fn () => $formatter->setPattern( $format ) )->format( $dateTime );
    }
}

if ( ! function_exists('carbon_locale') ) {
    /**
     * Alias to date_locale
     *
     * @deprecated
     * @param DateTime $dateTime
     * @param string $format
     * @return string
     */
    function carbon_locale(DateTime $dateTime, string $format = 'eeee d LLLL yyyy'): string
    {
        return date_locale( $dateTime, $format );
    }
}

if ( ! function_exists('use_locale') ) {
    /**
     * Change the locale during the execution of the given callback.
     *
     * @param string $locale
     * @param Closure $callback
     * @return void
     */
    function use_locale(string $locale, Closure $callback): void
    {
        $current_locale = app()->getLocale();
        app()->setLocale( $locale );

        $callback();

        app()->setLocale( $current_locale );
    }
}

if ( ! function_exists('json_from') ) {
    /**
     * Transform an array or an object into JSON.
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

if ( ! function_exists('force_logout') ) {
    /**
     * Force the user to log out.
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

if ( ! function_exists( 'user' ) ) {
    /**
     * Return the user.
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

if ( ! function_exists('image_srcset') ) {
    /**
     * Return a srcset attribute for image based on array of sizes. Sizes can be excluded using second parameters $except_sizes
     *
     * @param array $sizes
     * @param array $except_sizes
     * @return string
     */
    function image_srcset(array $sizes, array $except_sizes = []): string
    {
        $srcset = [];

        foreach ( $sizes as $key => $value ) {
            if (
                str_ends_with($key, '-width') ||
                str_ends_with($key, '-height') ||
                in_array( $key, $except_sizes )
            ) {
                continue;
            }

            $widthKey = $key . '-width';

            if ( ! isset( $sizes[ $widthKey ] ) ) {
                continue;
            }

            $srcset[] = sprintf(
                '%s %sw',
                $value,
                $sizes[ $widthKey ]
            );
        }

        usort($srcset, function ($a, $b) {
            preg_match('/(\d+)w$/', $a, $aMatch );
            preg_match('/(\d+)w$/', $b, $bMatch );

            return (int) $aMatch[1] <=> (int) $bMatch[1];
        } );

        $srcset = array_unique( $srcset );

        return implode(', ', $srcset );
    }
}

if ( ! function_exists('attachment_image_sizes') ) {
    /**
     * Extract then format the array of sizes from an attachment metadata.
     *
     * @param int $attachment_id
     * @return array
     */
    function attachment_image_sizes(int $attachment_id):array
    {
        $metadata = wp_get_attachment_metadata( $attachment_id );

        $dir = dirname($metadata['file']);

        $sizes = [];

        foreach ($metadata['sizes'] as $sizeName => $sizeData) {
            $sizes[ $sizeName ] = upload_url( $dir . '/' . $sizeData['file'] );
            $sizes[ $sizeName . '-width' ] = $sizeData['width'];
            $sizes[ $sizeName . '-height' ] = $sizeData['height'];
        }

        return $sizes;
    }
}

if ( ! function_exists('get_page_type') ) {
    /**
     * Return the current page type from WordPress
     *
     * @return string
     */
    function get_page_type(): string
    {
        $methods = [
            'is_embed'             => 'embed',
            'is_404'               => '404',
            'is_search'            => 'search',
            'is_front_page'        => 'front_page',
            'is_home'              => 'home',
            'is_privacy_policy'    => 'privacy_policy',
            'is_post_type_archive' => 'post_type_archive',
            'is_tax'               => 'tax',
            'is_attachment'        => 'attachment',
            'is_single'            => 'single',
            'is_page'              => 'page',
            'is_singular'          => 'singular',
            'is_category'          => 'category',
            'is_tag'               => 'tag',
            'is_author'            => 'author',
            'is_date'              => 'date',
            'is_archive'           => 'archive',
        ];

        foreach ( $methods as $function => $tag ) {
            if ( call_user_func( $function ) ) {
                return $tag;
            }
        }

        return 'undefined';
    }
}