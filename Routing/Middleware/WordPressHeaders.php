<?php

namespace Daedelus\Foundation\Routing\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 *
 */
class WordPressHeaders
{
	/** @var array|string[] */
	const array IGNORE_HEADERS = [
		'Cache-Control',
		'Expires',
		'Content-Type'
	];

	/**
	 * Handle an incoming request.
	 *
	 * @param  Request  $request
	 * @param Closure $next
	 *
	 * @return Response
	 */
	public function handle(Request $request, Closure $next ): Response
	{
		/** @var Response $response */
		$response = $next( $request );

		foreach ( headers_list() as $header ) {
			[ $header, $value ] = explode(': ', $header, 2 );

			if ( in_array( $header, self::IGNORE_HEADERS ) ) {
				continue;
			}

			if ( !headers_sent() ) {
				header_remove( $header );
			}

			$response->header( $header, $value, $header !== 'Set-Cookie' );
		}

		if ( !is_user_logged_in() ) {
			$response->setPublic();
		}

		return $response;
	}
}