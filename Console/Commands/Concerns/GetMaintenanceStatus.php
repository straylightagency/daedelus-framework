<?php

namespace Daedelus\Foundation\Console\Commands\Concerns;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 *
 */
trait GetMaintenanceStatus
{
	/**
	 * @return bool
	 * @throws FileNotFoundException
	 */
	protected function getStatus():bool
	{
		$file = app()->publicPath('.maintenance');

		if ( ! $this->files->exists( $file ) ) {
			return false;
		}

		$upgrading = 0;

		$contents = $this->files->get( $file );
		$matches  = [];

		if ( preg_match( '/upgrading\s*=\s*(\d+)\s*;/i', $contents, $matches ) ) {
			$upgrading = (int) $matches[1];
		} else {
			$this->warn('Unable to read the maintenance file timestamp, non-numeric value detected.');
		}

		if ( ( time() - $upgrading ) >= 10 * MINUTE_IN_SECONDS ) {
			return false;
		}

		return true;
	}
}