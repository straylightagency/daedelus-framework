<?php

namespace Daedelus\Foundation\Providers;

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
            ], 'majestic-dropins');

            $this->publishes( [
                __DIR__ . '/../dropins/object-cache.php' => content_path('object-cache.php'),
            ], 'majestic-dropins-objectcache');

            $this->publishes( [
                __DIR__ . '/../dropins/db.php' => content_path('db.php'),
            ], 'majestic-dropins-db');
        }
    }
}