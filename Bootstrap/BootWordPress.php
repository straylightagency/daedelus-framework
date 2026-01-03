<?php
namespace Daedelus\Foundation\Bootstrap;

use Daedelus\Foundation\Configuration\Configure;
use Illuminate\Contracts\Foundation\Application;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BootWordPress
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param Application $app
	 *
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
    public function bootstrap(Application $app):void
    {
	    if ( !defined( 'ABSPATH' ) ) {
		    define( 'ABSPATH', $app->publicPath('/') );
	    }

	    /** @var Configure $config */
	    $config = $app->get( Configure::class );
	    $config->apply();

	    if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
		    $_SERVER['HTTPS'] = 'on';
	    }

	    if ( !defined( 'DB_PREFIX' ) ) {
		    define( 'DB_PREFIX', 'wp_' );
	    }

	    $table_prefix = DB_PREFIX;

	    require_once $app->publicPath('wp-settings.php');
    }
}
