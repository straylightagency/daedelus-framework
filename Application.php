<?php
namespace Daedelus\Foundation;

use Closure;
use Daedelus\Foundation\Bootstrap\BootWordPress;
use Daedelus\Foundation\Configuration\ApplicationBuilder;
use Daedelus\Foundation\Configuration\Configure;
use Daedelus\Foundation\Routing\Middleware\WordPress404;
use Daedelus\Foundation\Routing\Middleware\WordPressContent;
use Daedelus\Foundation\Routing\Middleware\WordPressHeaders;
use Daedelus\Foundation\Routing\RoutingServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application as BaseApplication;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Http\Request;
use Illuminate\Log\LogServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 *
 */
class Application extends BaseApplication
{
	/**
	 * The Majestic framework version.
	 *
	 * @var string
	 */
	const string VERSION = '0.1.4';

	/** @var string */
	protected string $contentPath = 'content';

	/** @var string */
	protected string $themePath = '';

	/** @var string */
	protected string $themeUrl = '';

	/**
	 * Begin configuring a new Laravel application instance.
	 *
	 * @param string|null $basePath
	 *
	 * @return ApplicationBuilder
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public static function configure(?string $basePath = null): ApplicationBuilder
	{
		$basePath = is_string( $basePath ) ? $basePath : static::inferBasePath();

		return (new Configuration\ApplicationBuilder( new static( $basePath ) ) )
			->withKernels()
			->withEvents()
			->withCommands()
			->withProviders()
			->withFacades()
            ->withMiddleware( function ( Middleware $middleware ) {
                $middleware->group('wp', [
                    WordPressHeaders::class,
                    WordPress404::class,
                    WordPressContent::class
                ] );
            } )
            ->withConfig( function (Configure $config) {
                /**
                 * Set WordPress in the same environment type as Laravel
                 */
                $config->define( 'WP_ENVIRONMENT_TYPE', config('app.env', 'production') );

                /**
                 * Debug Mode
                 */
                $config->define( 'WP_DEBUG', is_debug() );
                $config->define( 'WP_DEBUG_DISPLAY', is_debug() );
                $config->define( 'SCRIPT_DEBUG', is_debug() );
                $config->define( 'SAVEQUERIES', is_debug() );

                /**
                 * Write the logs inside the storage/logs folder of Laravel
                 */
                $config->define( 'WP_DEBUG_LOG', storage_path( 'logs/wordpress-' . date('Y-m-d') . '.log' ) );

                /**
                 * Enable or disable the WpCache. Better to enable in production than development mode
                 */
                $config->define( 'WP_CACHE', env('WP_CACHE', false ) );

                /**
                 * Increase the memory limit during upload or heavy task
                 */
                $config->define( 'WP_MEMORY_LIMIT', env( 'WP_MEMORY_LIMIT', '64M' ) );
                $config->define( 'WP_MAX_MEMORY_LIMIT', env( 'WP_MAX_MEMORY_LIMIT', '256M' ) );

                /**
                 * Set the WordPress base URL using Laravel config
                 */
                $config->define( 'WP_HOME', app_url() );
                $config->define( 'WP_SITEURL', app_url() );

                /**
                 * Change wp-content folder name
                 */
                $config->define( 'WP_CONTENT_DIR', content_path() );
                $config->define( 'WP_CONTENT_URL', public_content_url() );

                /**
                 * Move the uploads folder outside the content directory
                 */
                $config->define( 'UPLOADS', 'uploads' );
                $config->define( 'WP_LANG_DIR', storage_path('lang') );

                /**
                 * WordPress Database using .env variables;
                 * these config constants are defined for backward compatibility with $wpdb
                 */
                $config->define( 'DB_NAME', env( 'DB_DATABASE', 'majestic' ) );
                $config->define( 'DB_USER', env( 'DB_USERNAME', 'root' ) );
                $config->define( 'DB_PASSWORD', env( 'DB_PASSWORD', 'root' ) );
                $config->define( 'DB_HOST', env( 'DB_HOST', 'localhost' ) );
                $config->define( 'DB_CHARSET', env( 'DB_CHARSET', 'utf8mb4' ) );
                $config->define( 'DB_COLLATE', env( 'DB_COLLATION', 'utf8mb4_unicode_ci' ) );
                $config->define( 'DB_PREFIX', env( 'DB_PREFIX', 'wp_' ) );

                /**
                 * Disallow to edit or update themes and plugins from admin when it's not in debug mode
                 */
                $config->define( 'DISALLOW_FILE_MODS', !is_debug() );
                $config->define( 'DISALLOW_FILE_EDIT', !is_debug() );
                $config->define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );

                /**
                 * Disable WordPress to download bases themes like twentytwentyfour
                 */
                $config->define( 'CORE_UPGRADE_SKIP_NEW_BUNDLED', true );

                /**
                 * Disable the Theme Files Editor
                 */
                $config->define( 'DISALLOW_FILE_EDIT', true );

                /**
                 * Authentication Unique Keys and Salts
                 */
                $config->define( 'AUTH_KEY', config( 'wordpress.auth_key' ) );
                $config->define( 'SECURE_AUTH_KEY', config( 'wordpress.secure_auth_key' ) );
                $config->define( 'LOGGED_IN_KEY', config( 'wordpress.logged_in_key' ) );
                $config->define( 'NONCE_KEY', config( 'wordpress.nonce_key' ) );
                $config->define( 'AUTH_SALT', config( 'wordpress.auth_salt' ) );
                $config->define( 'SECURE_AUTH_SALT', config( 'wordpress.secure_auth_salt' ) );
                $config->define( 'LOGGED_IN_SALT', config( 'wordpress.logged_in_salt' ) );
                $config->define( 'NONCE_SALT', config( 'wordpress.nonce_salt' ) );
            } );
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @return void
	 */
	protected function registerBaseBindings(): void
	{
		if ( is_null( static::$instance ) ) {
			static::setInstance( $this );
		}

		$this->instance( 'app', $this );

		$this->instance( Container::class, $this );

		$this->singleton( PackageManifest::class, fn () => new PackageManifest(
			new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
		) );
	}

	/**
	 * Register all the base service providers.
	 *
	 * @return void
	 */
	protected function registerBaseServiceProviders(): void
	{
		$this->register( new EventServiceProvider( $this ) );
		$this->register( new LogServiceProvider( $this ) );
		$this->register( new RoutingServiceProvider( $this ) );
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function contentPath(string $path = ''): string
	{
		return $this->publicPath($this->contentPath . ( $path != '' ? DIRECTORY_SEPARATOR . $path : '' ) );
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function contentUrl(string $path = ''): string
	{
		return config( 'app.url' ) .
		       str_replace( $this->publicPath(), '',
			       $this->publicPath( $this->contentPath . ( $path != '' ? DIRECTORY_SEPARATOR . $path : '' ) )
		       );
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function uploadPath(string $path = ''): string
	{
		return $this->publicPath('uploads' . ( $path != '' ? DIRECTORY_SEPARATOR . $path : '' ) );
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function muPluginsPath(string $path = ''): string
	{
		return $this->contentPath('mu-plugins' . ( $path != '' ? DIRECTORY_SEPARATOR . $path : '' ) );
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function pluginsPath(string $path = ''): string
	{
		return $this->contentPath('plugins' . ( $path != '' ? DIRECTORY_SEPARATOR . $path : '' ) );
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function themePath(string $path = ''): string
	{
		return $this->themePath . ( $path != '' ? DIRECTORY_SEPARATOR . $path : '');
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function themeUrl(string $path = ''): string
	{
		return $this->themeUrl . ( $path != '' ? DIRECTORY_SEPARATOR . $path : '');
	}

	/**
	 * @param string $path
	 * @return $this
	 */
	public function setThemePath(string $path): static
	{
		$this->themePath = $path;

		return $this;
	}

	/**
	 * @param string $url
	 * @return $this
	 */
	public function setThemeUrl(string $url): static
	{
		$this->themeUrl = $url;

		return $this;
	}

	/**
	 * @param Closure $closure
	 *
	 * @return void
	 */
	public function beforeBootingWordPress(Closure $closure):void
	{
		$this->beforeBootstrapping( BootWordPress::class, $closure );
	}

	/**
	 * @param Closure $closure
	 *
	 * @return void
	 */
	public function afterBootingWordPress(Closure $closure):void
	{
		$this->afterBootstrapping( BootWordPress::class, $closure );
	}

	/**
	 * @param Request $request *
	 *
	 * @return void
	 * @throws BindingResolutionException
	 */
	public function handleRequest(Request $request): void
	{
		/** @var HttpKernelContract $kernel */
		$kernel = $this->make( HttpKernelContract::class );

		$kernel->handle( $request );
	}

	/**
	 * Handle the incoming Artisan command.
	 *
	 * @param InputInterface $input
	 *
	 * @return int
	 * @throws BindingResolutionException
	 */
	public function handleCommand(InputInterface $input): int
	{
		$kernel = $this->make( ConsoleKernelContract::class );

		$status = $kernel->handle(
			$input,
			new ConsoleOutput
		);

		$kernel->terminate( $input, $status );

		return $status;
	}

	/**
	 * @param ...$hidden_files
	 *
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws InvalidArgumentException
	 * @throws NotFoundExceptionInterface
	 * @throws FileNotFoundException
	 */
	public function loadPlugins( ...$hidden_files ): void
	{
		( new PluginsRepository( $this, new Filesystem ) )
			->load( ...$hidden_files );
	}

	/**
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function registerHooks():void
	{
		$this->get( HooksRepository::class )->register();
	}

	/**
	 * Get the path to the cached packages.php file.
	 *
	 * @return string
	 */
	public function getCachedPluginsPath(): string
	{
		return $this->normalizeCachePath('APP_PLUGINS_CACHE', 'cache/must-use-plugins.php');
	}

	/**
	 * Determine if the application configuration is cached.
	 *
	 * @return bool
	 */
	public function pluginsManifestIsCached(): bool
	{
		return is_file( $this->getCachedPluginsPath() );
	}

	/**
	 * Get the version number of the application.
	 *
	 * @return string
	 */
	public function version(): string
	{
		return parent::VERSION . ' (Majestic ' . static::VERSION . ') ';
	}
}