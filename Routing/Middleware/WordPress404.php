<?php

namespace Daedelus\Foundation\Routing\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Transform a WP 404 into a 404 Response
 */
class WordPress404
{
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
		global $wp_query;

		/** @var Response $response */
		$response = $next( $request );

		if ( $wp_query->is_404() ) {
			$response->setStatusCode('404');
		}

		return $response;
	}
}