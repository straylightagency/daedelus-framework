<?php

namespace Daedelus\Foundation\Bootstrap;

use Illuminate\Foundation\Bootstrap\HandleExceptions as BaseHandleExceptions;
use Throwable;

class HandleExceptions extends BaseHandleExceptions
{
	/**
	 * Ignore deprecation errors
	 *
	 * @return bool
	 */
	protected function shouldIgnoreDeprecationErrors(): bool
	{
		return true;
	}

	/**
	 * Render an exception as an HTTP response and send it.
	 *
	 * @param Throwable $e
	 *
	 * @return void
	 */
	protected function renderHttpResponse(Throwable $e): void
	{
		if ( ob_get_length() ) {
			ob_end_clean();
		}

		parent::renderHttpResponse( $e );
	}
}
