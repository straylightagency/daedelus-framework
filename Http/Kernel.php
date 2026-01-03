<?php
namespace Daedelus\Foundation\Http;

use Daedelus\Support\Actions;
use Illuminate\Contracts\Container\BindingResolutionException;
use Daedelus\Foundation\Bootstrap\HandleExceptions;
use Daedelus\Foundation\Bootstrap\LoadConfiguration;
use Daedelus\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Daedelus\Foundation\Bootstrap\RegisterProviders;
use Daedelus\Foundation\Bootstrap\BootWordPress;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route as Routing;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Illuminate\Foundation\Http\Kernel as BaseKernel;

/**
 *
 */
class Kernel extends BaseKernel
{
	use InteractsWithTime;

    /**
     * The router instance.
     *
     * @var \Daedelus\Foundation\Routing\Router
     */
    protected $router;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var string[]
	 */
	protected $bootstrappers = [
		LoadEnvironmentVariables::class,
		LoadConfiguration::class,
		HandleExceptions::class,
		RegisterFacades::class,
		RegisterProviders::class,
		BootProviders::class,
		BootWordPress::class,
	];

	/** @var Request */
	protected Request $request;

	/**
	 * Handle an incoming HTTP request.
	 *
	 * @param Request $request
	 *
	 * @return void
	 */
	public function handle($request): void
	{
		$this->request = $request;

		$this->requestStartedAt = Carbon::now();

		$request->enableHttpMethodParameterOverride();

		$this->app->instance( 'request', $request );

		Facade::clearResolvedInstance('request');

		$this->bootstrap();

		Actions::add('template_redirect', fn () => ob_start() );
		Actions::remove('template_redirect', 'redirect_canonical');
		Actions::remove('shutdown', 'wp_ob_end_flush_all', 1 );

		Actions::add('parse_request', function () use ($request) {
            $this->syncMiddlewareToRouter();

            try {
                $request->merge( [ 'is_laravel_request' => true ] );

                $response = $this->handlingRequest( $request,
                    $this->matchRoute( $request )
                );

                $this->sendResponse( $request, $response );
            } catch ( MethodNotAllowedHttpException | NotFoundHttpException $exception ) {
                $request->merge( [ 'is_laravel_request' => false ] );

                $path = Str::finish( $request->getBaseUrl(), $request->getPathInfo() );

                $except = collect( [
                    admin_url(),
                    wp_login_url(),
                    wp_registration_url(),
                    rest_url(),
                ] )->map( fn ( string $url ) => parse_url( $url, PHP_URL_PATH ) )->unique()->filter();

                $api_url = parse_url( rest_url(), PHP_URL_PATH );

                if (
                    Str::startsWith( $path, $except->all() ) ||
                    Str::endsWith( $path, '.php' ) ||
                    ( Str::startsWith( $path, $api_url ) && redirect_canonical(null, false) ) )
                {
                    return;
                }

                $route = $this->registerWordPressRoute()->bind( $request );

                $response = $this->handlingRequest( $request, $route );

                Actions::add('shutdown', fn () => $this->sendResponse( $request, $response ), 100 );
            } catch ( Throwable $throwable ) {
                $this->reportException( $throwable );

                $response = $this->renderException( $request, $throwable );

                $this->sendResponse( $request, $response );
            }
		} );
	}

	/**
	 * @param Request $request
	 * @param Route $route
	 *
	 * @return Response
     */
	protected function handlingRequest(Request $request, Route $route): Response
	{
        return ( new Pipeline( $this->app ) )
            ->send( $request )
            ->pipe( $this->app->shouldSkipMiddleware() ? [] : $this->middleware )
            ->then( fn ( $request ) => $this->router->runRoute( $request, $route ) );
	}

	/**
	 * @param $request
	 * @param $response
	 *
	 * @return void
	 * @throws BindingResolutionException
	 */
	protected function sendResponse($request, $response): void
	{
		$this->app['events']->dispatch(
			new RequestHandled( $request, $response )
		);

		$response->send();

		$this->terminate( $request, $response );
	}

	/**
	 * Register the default WordPress route.
	 */
	protected function registerWordPressRoute(): Route
	{
		return Routing::any('{__wordpress?}', fn () => response('') )
		     ->middleware( [ 'web', 'wp' ] )
		     ->where('__wordpress', '.*')
		     ->name('wordpress');
	}

    /**
     * @param Request $request
     * @return Route
     * @throws BindingResolutionException
     */
    protected function matchRoute(Request $request): Route
    {
        /** @var \Daedelus\Foundation\Routing\Router $router */
        $router = $this->app->make( Router::class );

        return $router->getRoutes()->match( $request );
    }
}