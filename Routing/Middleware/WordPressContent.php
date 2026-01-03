<?php

namespace Daedelus\Foundation\Routing\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 *
 */
class WordPressContent
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
		/** @var Response $response */
		$response = $next( $request );

		$content = '';

		$levels = ob_get_level();

		for ( $i = 0; $i < $levels; $i++ ) {
			$content .= ob_get_clean();
		}

		$response->setContent( $content );

		return $response;
	}
}