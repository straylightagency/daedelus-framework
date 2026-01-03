<?php
namespace Daedelus\Foundation\Console\Commands\Concerns;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 *
 */
trait CanRequestWpOrg
{
	/**
	 * WordPress.org API root URL.
	 *
	 * @var string
	 */
	const string API_ROOT = 'https://api.wordpress.org';

	/**
	 * WordPress.org API root URL.
	 *
	 * @var string
	 */
	const string DOWNLOADS_ROOT = 'https://downloads.wordpress.org';

	/**
	 * Core checksums endpoint.
	 *
	 * @see https://codex.wordpress.org/WordPress.org_API#Checksum
	 *
	 * @var string
	 */
	const string CORE_CHECKSUMS_ENDPOINT = self::API_ROOT . '/core/checksums/1.0/';

	/**
	 * Plugin checksums endpoint.
	 *
	 * @var string
	 */
	public final const PLUGIN_CHECKSUMS_ENDPOINT = self::DOWNLOADS_ROOT . '/plugin-checksums/';

	/**
	 * Plugin info endpoint.
	 *
	 * @var string
	 */
	const string PLUGIN_INFO_ENDPOINT = self::API_ROOT . '/plugins/info/1.2/';

	/**
	 * Theme info endpoint.
	 *
	 * @var string
	 */
	const string THEME_INFO_ENDPOINT = self::API_ROOT . '/themes/info/1.2/';

	/**
	 * Salt endpoint.
	 *
	 * @see https://codex.wordpress.org/WordPress.org_API#Secret_Key
	 *
	 * @var string
	 */
	const string SALT_ENDPOINT = self::API_ROOT . '/secret-key/1.1/salt/';

	/**
	 * Version check endpoint.
	 *
	 * @see https://codex.wordpress.org/WordPress.org_API#Version_Check
	 *
	 * @var string
	 */
	const string VERSION_CHECK_ENDPOINT = self::API_ROOT . '/core/version-check/1.7/';

	/**
	 * @param string $version
	 * @param string $locale
	 *
	 * @return array|false
	 * @throws ConnectionException
	 */
	protected function getCoreVersionChecksums(string $version, string $locale = 'en_US'):array|false
	{
		$url = sprintf(
			'%s?%s',
			self::CORE_CHECKSUMS_ENDPOINT,
			http_build_query( [
				'version' => $version,
				'locale'  => $locale,
			], '', '&' )
		);

		$response = $this->sendJsonRequestWpOrg( $url );

		if (
			! is_array( $response )
			|| ! isset( $response['checksums'] )
			|| ! is_array( $response['checksums'] )
		) {
			return false;
		}

		return data_get( $response, 'checksums' );
	}

	/**
	 * @param string $locale
	 *
	 * @return array|false
	 * @throws ConnectionException
	 */
	protected function getCoreVersionCheck(string $locale = 'en_US'):array|false
	{
		$url = sprintf(
			'%s?%s',
			self::VERSION_CHECK_ENDPOINT,
			http_build_query( [ 'locale' => $locale ], '', '&' )
		);

		$response = $this->sendJsonRequestWpOrg( $url );

		if ( ! is_array( $response ) ) {
			return false;
		}

		return $response;
	}

	/**
	 * @param string $locale
	 *
	 * @return array|false
	 * @throws ConnectionException
	 */
	protected function getCoreDownloadOffer(string $locale = 'en_US'):array|false
	{
		$response = $this->getCoreVersionCheck( $locale );

		if ( ! isset( $response['offers'] ) || ! is_array( $response['offers'] ) ) {
			return false;
		}

		$offer = $response['offers'][0];

		if ( ! array_key_exists( 'locale', $offer ) || $locale !== $offer['locale'] ) {
			return false;
		}

		return $offer;
	}

	/**
	 * @param string $plugin
	 * @param string $version
	 *
	 * @return array|false
	 * @throws ConnectionException
	 */
	protected function getPluginCheckSums(string $plugin, string $version):array|false
	{
		$url = sprintf(
			'%s%s/%s.json',
			self::PLUGIN_CHECKSUMS_ENDPOINT,
			$plugin,
			$version
		);

		$response = $this->sendJsonRequestWpOrg( $url );

		return data_get( $response, 'files', false );
	}

	/**
	 * @param string $plugin
	 * @param string $locale
	 * @param array $fields
	 *
	 * @return array|false
	 * @throws ConnectionException
	 */
	protected function getPluginInfo(string $plugin, string $locale = 'en_US', array $fields = []):array|false
	{
		$action  = 'plugin_information';

		$request = [
			'locale' => $locale,
			'slug'   => $plugin,
		];

		if ( ! empty( $fields ) ) {
			$request['fields'] = $fields;
		}

		$url = sprintf(
			'%s?%s',
			self::PLUGIN_INFO_ENDPOINT,
			http_build_query( compact( 'action', 'request' ), '', '&' )
		);

		$plugin_info = $this->sendJsonRequestWpOrg( $url );

		if ( ! is_array( $plugin_info ) ) {
			return false;
		}

		return $plugin_info;
	}

	/**
	 * @param string $theme
	 * @param string $locale
	 * @param array $fields
	 *
	 * @return array|false
	 * @throws ConnectionException
	 */
	protected function getThemeInfo(string $theme, string $locale = 'en_US', array $fields = []):array|false
	{
		$action  = 'plugin_information';

		$request = [
			'locale' => $locale,
			'slug'   => $theme,
		];

		if ( ! empty( $fields ) ) {
			$request['fields'] = $fields;
		}

		$url = sprintf(
			'%s?%s',
			self::THEME_INFO_ENDPOINT,
			http_build_query( compact( 'action', 'request' ), '', '&' )
		);

		$theme_info = $this->sendJsonRequestWpOrg( $url );

		if ( ! is_array( $theme_info ) ) {
			return false;
		}

		return $theme_info;
	}

	/**
	 * @return string
	 * @throws ConnectionException
	 */
	protected function getSalts():string
	{
		return $this->sendRequestWpOrg( self::SALT_ENDPOINT )->body();
	}

	/**
	 * @param string $url
	 * @param array $headers
	 *
	 * @return mixed
	 * @throws ConnectionException
	 */
	protected function sendJsonRequestWpOrg(string $url, array $headers = []):mixed
	{
		$headers = [ 'Accept' => 'application/json',
			...$headers
		];

		return $this->sendRequestWpOrg( $url, $headers )->json();
	}

	/**
	 * @param string $url
	 * @param array $headers
	 *
	 * @return Response
	 * @throws ConnectionException
	 */
	protected function sendRequestWpOrg(string $url, array $headers = []): Response
	{
		$response = Http::withHeaders( $headers )->get( $url );

		if ( 200 > $response->status() ||
		     300 <= $response->status() ) {
			throw new RuntimeException(
				"Couldn't fetch response from {$url} (HTTP code {$response->status()})."
			);
		}

		return $response;
	}
}