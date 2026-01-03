<?php

namespace Daedelus\Foundation;

use Illuminate\Foundation\ViteException;
use Illuminate\Support\Arr;
use Throwable;

/**
 */
class Vite
{
	/** @var bool */
	protected bool $isDevMode = false;

    /** @var string */
    protected string $assetsDir = '';

	/** @var string */
	protected string $manifestPath = '';

	/** @var string */
	protected string $host = 'http://localhost:5173/';

	/** @var bool */
	protected bool $isInitialized = false;

    /** @var array */
    protected array $cache = [];

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setAssetsDir(string $path): static
    {
        $this->assetsDir = $path;

        return $this;
    }

	/**
	 * @param string $path
	 *
	 * @return $this
	 */
	public function setManifestPath(string $path): static
	{
		$this->manifestPath = $path;

		return $this;
	}

	/**
	 * @param string $host
	 *
	 * @return $this
	 */
	public function setHost(string $host): static
	{
		$this->host = $host;

		return $this;
	}

    /**
     * @param bool $isDevMode
     *
     * @return $this
     */
    public function setDevMode(bool $isDevMode): static
    {
        $this->isDevMode = $isDevMode;

        return $this;
    }

	/**
	 * Generate Vite tags
	 * @throws ViteException
	 */
	public function asset(array|string $entries): string
	{
		$entries = Arr::wrap( $entries );

		if ( $this->isDevMode && $this->isRunningHot() ) {
			return $this->assetDev( $entries );
		}

		return $this->assetProd( $entries );
	}

	/**
	 * @param array $entries
	 *
	 * @return string
     */
	protected function assetDev(array $entries): string
	{
		$entries = collect( $entries );

		if ( !$this->isInitialized ) {
			$entries->prepend('@vite/client');

			$this->isInitialized = true;
		}

		return $entries->map( fn ( $entry ) => $this->makeTag( $entry, $this->host ) )->join('');
	}

	/**
	 * @param array $entries
	 *
	 * @return string
	 * @throws ViteException
	 */
	protected function assetProd(array $entries): string
	{
		$entries = collect( $entries );

		if ( !file_exists( $this->manifestPath ) ) {
			throw new ViteException( sprintf( 'Vite manifest not found at: %s', $this->manifestPath ) );
		}

        if ( empty( $this->cache ) ) {
            $this->cache = json_decode( file_get_contents( $this->manifestPath ), true );
        }

		return $entries->map( fn ($entry) => $this->makeTagForChunk( $entry, '/' . $this->assetsDir . '/', $this->cache ) )->join('');
	}

	/**
	 * @param string $entry
	 * @param string $base
	 *
	 * @return string
	 */
	protected function makeTag(string $entry, string $base): string
	{
		$html = '';

		if ( $this->isCssFile( $entry ) ) {
			$html .= $this->makeStylesheetTag( $base . $entry );
		} else {
			$html .= $this->makeScriptTag( $base . $entry );
		}

		return $html;
	}

	/**
	 * @param string $entry
	 * @param string $base
	 * @param array $manifest
	 *
	 * @return string
	 */
	protected function makeTagForChunk(string $entry, string $base, array $manifest): string
	{
		$file = $manifest[ $entry ][ 'file' ];
		$css = $manifest[ $entry ][ 'css' ] ?? [];
		$imports = $manifest[ $entry ][ 'imports' ] ?? [];

        $html = $this->makeTag( $file, $base );

		foreach( $css as $file ) {
			$html .= $this->makeStylesheetTag( $base . $file );
		}

		foreach ( $imports as $file_key ) {
            if ( isset( $manifest[ $file_key ] ) ) {
                $file = $manifest[ $file_key ]['file'];

                $html .= $this->makeModuleTag( $base . $file );
            }
		}

		return $html;
	}

	/**
	 * @param string $file_path
	 *
	 * @return string
	 */
	protected function makeScriptTag(string $file_path): string
	{
		return '<script type="module" src="' . $file_path . '" defer></script>';
	}

	/**
	 * @param string $file_path
	 *
	 * @return string
	 */
	protected function makeStylesheetTag(string $file_path): string
	{
		return '<link rel="stylesheet" media="screen" href="' . $file_path . '"/>';
	}

	/**
	 * @param string $file_path
	 *
	 * @return string
	 */
	protected function makeModuleTag(string $file_path): string
	{
		return '<link rel="modulepreload" href="' . $file_path . '"/>';
	}

	/**
	 * @param $entry
	 *
	 * @return bool
	 */
	protected function isCssFile( $entry ):bool
	{
		return str_ends_with( $entry, '.css' );
	}

	/**
	 * @return bool
	 */
	protected function isRunningHot():bool
	{
		try {
			return file_get_contents( $this->host . 'hot' ) === 'true';
		} catch ( Throwable $e ) {
			return false;
		}
	}
}