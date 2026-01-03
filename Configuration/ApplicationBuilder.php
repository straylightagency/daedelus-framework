<?php
namespace Daedelus\Foundation\Configuration;

use Closure;
use Daedelus\Foundation\Application;
use Daedelus\Foundation\Bootstrap\RegisterFacades;
use Daedelus\Foundation\HooksRepository;
use Daedelus\Foundation\Mix;
use Daedelus\Foundation\Vite;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Foundation\Configuration\ApplicationBuilder as BaseApplicationBuilder;

/**
 *
 */
class ApplicationBuilder extends BaseApplicationBuilder
{
    /**
     * Create a new application builder instance.
     */
    public function __construct(Application $app)
    {
        parent::__construct( $app );
    }

    /**
     * @return $this
     */
    public function withKernels(): static
    {
        $this->app->singletonIf(
            \Illuminate\Contracts\Http\Kernel::class,
            \Daedelus\Foundation\Http\Kernel::class
        );

        $this->app->singletonIf(
            \Illuminate\Contracts\Console\Kernel::class,
            \Daedelus\Foundation\Console\Kernel::class
        );

        return $this;
    }

    /**
     * @param Closure $closure
     *
     * @return $this
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function withConfig(Closure $closure): static
    {
        $this->app->singletonIf(Configure::class );

        $this->app->beforeBootingWordPress( function (Application $app) use ($closure) {
            $closure( $app->get( Configure::class ) );
        } );

        return $this;
    }

    /**
     * @param string $assets_dir
     * @param string $manifest_path
     * @param bool|null $dev_mode
     *
     * @return $this
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function withVite(string $assets_dir = 'dist', string $manifest_path = 'manifest.json', ?bool $dev_mode = null): static
    {
        $this->app->singletonIf( Vite::class );

        $this->app->booted( function () use ( $assets_dir, $manifest_path, $dev_mode ) {
            $assets_dir = trim( $assets_dir, '/' );

            /** @var Vite $vite */
            $vite = $this->app->get( Vite::class );

            $vite->setAssetsDir( $assets_dir );
            $vite->setManifestPath( public_path( $assets_dir . DIRECTORY_SEPARATOR . $manifest_path ) );
            $vite->setDevMode( is_bool( $dev_mode ) ? $dev_mode : !app()->isProduction() );
        } );

        return $this;
    }

    /**
     * @param string $assets_dir
     * @param string $manifest_path
     * @param bool|null $dev_mode
     *
     * @return $this
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function withMix(string $assets_dir = 'dist', string $manifest_path = 'mix-manifest.json', ?bool $dev_mode = null): static
    {
        $this->app->singletonIf( Mix::class );

        $this->app->booted( function () use ( $assets_dir, $manifest_path, $dev_mode ) {
            $assets_dir = trim( $assets_dir, '/' );

            /** @var Mix $mix */
            $mix = $this->app->get( Mix::class );
            $mix->setAssetsDir( $assets_dir );
            $mix->setManifestPath( $manifest_path );
            $mix->setDevMode( is_bool( $dev_mode ) ? $dev_mode : !app()->isProduction() );
        } );

        return $this;
    }

    /**
     * @param array $hooks
     *
     * @return $this
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function withHooks(array $hooks = []): static
    {
        $this->app->singletonIf( HooksRepository::class );

        $this->app->get( HooksRepository::class )->addHooks( $hooks );

        return $this;
    }

    /**
     * Register additional service providers.
     *
     * @param  array  $facades
     * @return $this
     */
    public function withFacades(array $facades = []): static
    {
        RegisterFacades::merge( $facades );

        return $this;
    }
}