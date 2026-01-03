<?php

namespace Daedelus\Foundation;

use Exception;
use Illuminate\Foundation\MixManifestNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

/**
 *
 */
class Mix
{
    /** @var bool */
    protected bool $isDevMode = false;

    /** @var string */
    protected string $assetsDir = '';

    /** @var string */
    protected string $manifestPath = '';

    /** @var string */
    protected string $host = 'http://localhost:8080';

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
     * Generate assets tags
     * @throws Exception
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
     * @throws MixManifestNotFoundException
     */
    protected function assetDev(array $entries): string
    {
        $real_manifest_path = public_path( $this->assetsDir . DIRECTORY_SEPARATOR . $this->manifestPath );

        if ( !file_exists( $real_manifest_path ) ) {
            throw new MixManifestNotFoundException( sprintf( 'Mix manifest not found at: %s', $real_manifest_path ) );
        }

        if ( empty( $this->cache ) ) {
            $this->cache = Arr::mapWithKeys(
                json_decode( file_get_contents( rtrim( $this->host ) . '/' . $this->manifestPath ), true ),
                fn (string $entry, string $path) => [ ltrim( $path, '/') => $entry ]
            );
        }

        return collect( $entries )
            ->map(
                fn (string $entry) => $this->makeTag( $entry, rtrim( $this->host ), $this->cache )
            )
            ->join('');
    }

    /**
     * @param array $entries
     *
     * @return string
     * @throws Exception
     */
    protected function assetProd(array $entries): string
    {
        $assets_dir = Str::start( $this->assetsDir, '/' );
        $real_manifest_path = public_path( $assets_dir . DIRECTORY_SEPARATOR . $this->manifestPath );

        if ( !file_exists( $real_manifest_path ) ) {
            throw new MixManifestNotFoundException( sprintf( 'Mix manifest not found at: %s', $real_manifest_path ) );
        }

        if ( empty( $this->cache ) ) {
            $this->cache = Arr::mapWithKeys(
                json_decode( file_get_contents( $real_manifest_path ), true ),
                fn (string $entry, string $path) => [ ltrim( $path, '/') => $entry ]
            );
        }

        return collect( $entries )
            ->map(
                fn (string $entry) => $this->makeTag( $entry, $assets_dir, $this->cache ) )
            ->join('');
    }

    /**
     * @param string $entry
     * @param string $base
     * @param array $manifest
     *
     * @return string
     */
    protected function makeTag(string $entry, string $base, array $manifest): string
    {
        $file = $manifest[ $entry ];

        if ( $this->isCssFile( $file ) ) {
            return $this->makeStylesheetTag( rtrim( $base ) . $file );
        } else {
            return $this->makeScriptTag( rtrim( $base ) . $file );
        }
    }

    /**
     * @param string $file_path
     *
     * @return string
     */
    protected function makeScriptTag(string $file_path): string
    {
        return '<script src="' . $file_path . '" defer></script>';
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
            return trim( file_get_contents( rtrim( $this->host, '/') . '/' . 'hot' ) ) === trim( $this->host, '/');
        } catch ( Throwable $e ) {
            return false;
        }
    }
}