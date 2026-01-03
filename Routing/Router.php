<?php

namespace Daedelus\Foundation\Routing;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router as BaseRouter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Override the Laravel Router
 */
class Router extends BaseRouter
{
	/**
	 * Return the response for the given route.
	 * @override Change visibility from projected to public
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Routing\Route  $route
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function runRoute(Request $request, Route $route): Response
	{
		return parent::runRoute( $request, $route );
	}

	/**
	 * Run the given route within a Stack "onion" instance.
	 * @override Use the base Pipeline instead of the Router Pipeline
	 *
	 * @param \Illuminate\Routing\Route $route
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return mixed
	 * @throws BindingResolutionException
	 */
	protected function runRouteWithinStack(Route $route, Request $request): mixed
	{
		$shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
		                        $this->container->make('middleware.disable') === true;

		$middleware = $shouldSkipMiddleware ? [] : $this->gatherRouteMiddleware( $route );

		return ( new Pipeline( $this->container ) )
			->send( $request )
			->through( $middleware )
			->then( fn ( $request ) => $this->prepareResponse(
				$request, $route->run()
			) );
	}
}