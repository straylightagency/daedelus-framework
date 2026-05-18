<?php

namespace Daedelus\Framework\Routing\Middleware;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle HTTP headers from WordPress.
 */
class WordPressHeaders
{
    /** @var array|string[] */
    const array IGNORE_HEADERS = [
        'X-Redirect-By',
        'X-Powered-By',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        global $wp_query;

        /** @var Response $response */
        $response = $next( $request );

        $status_code = $wp_query->is_404() ? 404 : http_response_code();

        $headers = array_map( function( $header ) {
            [ $header, $value ] = explode(': ', $header, 2 );

            return [ $header => $value ];
        }, headers_list() );

        $headers = array_filter( array_merge( ...$headers ), function( $value, $header ) {
            if ( ! headers_sent() ) {
                header_remove( $header );
            }

            return ! in_array( $header, self::IGNORE_HEADERS );
        }, ARRAY_FILTER_USE_BOTH );

        foreach ( $headers as $header => $value ) {
            if ( $header === 'Location' ) {
                unset( $headers[ $header ] );
                $response = redirect( $value, $status_code, $headers );
                break;
            }

            $response->header( $header, $value, $header !== 'Set-Cookie' );
        }

        $response->setStatusCode( $status_code );

        if ( ! is_user_logged_in() ) {
            $response->setPublic();
        }

        return $response;
    }
}