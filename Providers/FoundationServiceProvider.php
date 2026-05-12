<?php

namespace Daedelus\Framework\Providers;

use Illuminate\Support\ServiceProvider;

/**
 *
 */
class FoundationServiceProvider extends ServiceProvider
{
    /**
     * Publish core assets.
     */
    public function boot(): void
    {
        if ( $this->app->runningInConsole() ) {
            $this->publishes( [
                __DIR__ . '/../dropins' => content_path(),
            ], 'daedelus-dropins');

            $this->publishes( [
                __DIR__ . '/../dropins/object-cache.php' => content_path('object-cache.php'),
            ], 'daedelus-dropins-objectcache');

            $this->publishes( [
                __DIR__ . '/../dropins/db.php' => content_path('db.php'),
            ], 'daedelus-dropins-db');
        }
    }
}