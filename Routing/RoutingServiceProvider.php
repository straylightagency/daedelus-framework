<?php

namespace Daedelus\Foundation\Routing;

use Illuminate\Routing\RoutingServiceProvider as BaseRoutingServiceProvider;

/**
 *
 */
class RoutingServiceProvider extends BaseRoutingServiceProvider
{
	/**
	 * Register the router instance.
	 *
	 * @return void
	 */
	protected function registerRouter():void
	{
		$this->app->singleton('router', fn ( $app ) => new Router( $app['events'], $app ) );
	}
}