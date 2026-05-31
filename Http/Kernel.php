<?php
namespace Daedelus\Framework\Http;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Daedelus\Support\Actions;
use Daedelus\Support\Filters;
use Illuminate\Pipeline\Pipeline;
use Daedelus\Framework\Routing\Router;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\InteractsWithTime;
use Symfony\Component\HttpFoundation\Response;
use Daedelus\Framework\Bootstrap\BootWordPress;
use Illuminate\Support\Facades\Route as Routing;
use Daedelus\Framework\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Daedelus\Framework\Bootstrap\HandleExceptions;
use Daedelus\Framework\Bootstrap\LoadConfiguration;
use Daedelus\Framework\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Http\Kernel as BaseKernel;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 *
 */
class Kernel extends BaseKernel
{
    use InteractsWithTime;

    /**
     * The router instance.
     *
     * @var \Daedelus\Framework\Routing\Router
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

        Actions::remove('template_redirect', 'redirect_canonical');
        Actions::remove('shutdown', 'wp_ob_end_flush_all', 1 );

        Filters::add('do_parse_request', function () use ($request) {
            $this->syncMiddlewareToRouter();

            $path = Str::finish( $request->getBaseUrl(), $request->getPathInfo() );

            $except = collect( [
                rest_url(),
                admin_url(),
                wp_login_url(),
                wp_registration_url(),
            ] )->map( fn ( string $url ) => parse_url( $url, PHP_URL_PATH ) )->unique()->filter();

            $api_url = parse_url( rest_url(), PHP_URL_PATH );

            if ( Str::startsWith( $path, $except->all() ) || Str::endsWith( $path, '.php' ) ||
                ( Str::startsWith( $path, $api_url ) && redirect_canonical(null, false) ) ) {
                return true;
            }

            try {
                $route = $this->router->findRoute( $request );

                $response = $this->handlingRequest( $request, $route );

                $this->sendResponse( $request, $response );

                return false;
            } catch ( NotFoundHttpException | MethodNotAllowedHttpException $e ) {
                return true;
            } catch ( Throwable $throwable ) {
                $this->reportException( $throwable );

                $this->sendResponse( $request,
                    $this->renderException( $request, $throwable )
                );
            }
        } );

        Actions::add('parse_request', function () use ($request) {
            $this->app->handlingWordPress();

            $route = $this->registerWordPressRoute( $request );

            Actions::add('shutdown', fn () => $this->sendResponse( $request,
                $this->handlingRequest( $request, $route )
            ), 100 );
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
     * @param Request $request
     * @param Response $response
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
     *
     * @param Request $request
     * @return Route
     */
    protected function registerWordPressRoute(Request $request): Route
    {
        return tap( new Route('any', '{__wordpress?}',
            fn () => Filters::apply('daedelus/render', '' ) ),
            fn ($route) =>
            $route->setRouter( $this->router )
                ->setContainer( $this->app )
                ->middleware( [ 'web', 'wp' ] )
                ->where('__wordpress', '.*')
                ->name('wordpress')
                ->bind( $request )
        );
    }
}