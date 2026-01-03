<?php

namespace Daedelus\Foundation\Console\Commands\Concerns\Utils;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 *
 */
class HttpCacheManager
{
	/** @var ?HttpCacheManager */
	protected static ?HttpCacheManager $instance = null;

	/** @var Repository */
	protected Repository $cache;

	/** @var array */
	protected array $whitelist = [];

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->cache = Cache::store('file');

		// hook into wp http api
		add_filter( 'pre_http_request', [ $this, 'filterPreHttpRequest' ], 10, 3 );
		add_filter( 'http_response', [ $this, 'filterHttpResponse' ], 10, 3 );
	}

	/**
	 * @return static
	 */
	public static function getInstance(): static
	{
		if ( self::$instance === null ) {
			self::$instance = app( static::class );
		}

		return self::$instance;
	}

	/**
	 * Short circuit wp http api with cached file
	 *
	 * @param mixed $response
	 * @param array $args
	 * @param string $url
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function filterPreHttpRequest(mixed $response, array $args, string $url ): mixed
	{
		// check if whitelisted
		if ( ! isset( $this->whitelist[ $url ] ) ) {
			return $response;
		}

		// check if downloading
		if ( 'GET' !== $args['method'] || empty( $args['filename'] ) ) {
			return $response;
		}

		// check cache and export to designated location
		$filename = $this->cache->get( $this->whitelist[ $url ]['key'], false );

		if ( $filename && file_exists( $filename ) ) {
			if ( copy( $filename, $args['filename'] ) ) {
				// simulate successful download response
				return [
					'response' => [
						'code'    => 200,
						'message' => 'OK',
					],
					'filename' => $args['filename'],
				];
			}
		}

		return $response;
	}

	/**
	 * Cache wp http api downloads
	 *
	 * @param array $response
	 * @param array $args
	 * @param string $url
	 * @return array
	 */
	public function filterHttpResponse( array $response, array $args, string $url ): array
	{
		// check if whitelisted
		if ( ! isset( $this->whitelist[ $url ] ) ) {
			return $response;
		}

		// check if downloading
		if ( 'GET' !== $args['method'] || empty( $args['filename'] ) ) {
			return $response;
		}

		// check if download was successful
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $response;
		}

		// cache downloaded file
		$this->cache->put( $this->whitelist[ $url ]['key'], $response['filename'] );

		return $response;
	}

	/**
	 * @param string $url
	 * @param string $group
	 * @param string $slug
	 * @param string $version
	 * @param ?int $ttl
	 *
	 * @return void
	 */
	public function whitelistPackage(string $url, string $group, string $slug, string $version, ?int $ttl = null): void
	{
		$ext = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );

		$this->whitelistUrl( $url, "$group/$slug-$version.$ext", $ttl );

		wp_update_plugins();
	}

	/**
	 * @param string $url
	 * @param string $key
	 * @param int|null $ttl
	 *
	 * @return void
	 */
	public function whitelistUrl(string $url, string $key, ?int $ttl = null): void
	{
		$this->whitelist[ $url ] = [
			'key' => $key ? : $url,
			'ttl' => $ttl,
		];
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	public function isWhitelisted(string $url): bool
	{
		return isset( $this->whitelist[ $url ] );
	}
}