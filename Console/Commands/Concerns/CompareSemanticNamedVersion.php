<?php
namespace Daedelus\Foundation\Console\Commands\Concerns;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use UnexpectedValueException;

/**
 *
 */
trait CompareSemanticNamedVersion
{
	/**
	 * @param string $new_version
	 * @param string $original_version
	 *
	 * @return string
	 */
	protected function compareSemanticNamedVersion(string $new_version, string $original_version): string
	{
		if ( ! Comparator::greaterThan( $new_version, $original_version ) ) {
			return '';
		}

		$parts = explode( '-', $original_version );

		$bits  = explode( '.', $parts[0] );

		$major = $bits[0];

		if ( isset( $bits[1] ) ) {
			$minor = $bits[1];
		}

		try {
			if ( isset( $minor ) && Semver::satisfies( $new_version, "{$major}.{$minor}.x" ) ) {
				return 'patch';
			}

			if ( Semver::satisfies( $new_version, "{$major}.x.x" ) ) {
				return 'minor';
			}
		} catch ( UnexpectedValueException $e ) {
			return '';
		}

		return 'major';
	}
}