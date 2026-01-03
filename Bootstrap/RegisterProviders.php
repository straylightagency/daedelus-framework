<?php

namespace Daedelus\Foundation\Bootstrap;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\DefaultProviders;
use Illuminate\Foundation\Bootstrap\RegisterProviders as BaseRegisterProviders;

class RegisterProviders extends BaseRegisterProviders
{
	protected array $defaultProviders = [
		\Illuminate\Auth\AuthServiceProvider::class,
		\Illuminate\Broadcasting\BroadcastServiceProvider::class,
		\Illuminate\Bus\BusServiceProvider::class,
		\Daedelus\Foundation\Cache\CacheServiceProvider::class,
		\Daedelus\Foundation\Providers\FoundationServiceProvider::class,
		\Daedelus\Foundation\Providers\ConsoleSupportServiceProvider::class,
		\Illuminate\Cookie\CookieServiceProvider::class,
		\Illuminate\Database\DatabaseServiceProvider::class,
		\Illuminate\Encryption\EncryptionServiceProvider::class,
		\Illuminate\Filesystem\FilesystemServiceProvider::class,
		\Illuminate\Foundation\Providers\FoundationServiceProvider::class,
		\Illuminate\Hashing\HashServiceProvider::class,
		\Illuminate\Mail\MailServiceProvider::class,
		\Illuminate\Notifications\NotificationServiceProvider::class,
		\Illuminate\Pagination\PaginationServiceProvider::class,
		\Illuminate\Pipeline\PipelineServiceProvider::class,
		\Illuminate\Queue\QueueServiceProvider::class,
		\Illuminate\Redis\RedisServiceProvider::class,
		\Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
		\Illuminate\Session\SessionServiceProvider::class,
		\Illuminate\Translation\TranslationServiceProvider::class,
		\Illuminate\Validation\ValidationServiceProvider::class,
		\Daedelus\Foundation\View\ViewServiceProvider::class,
	];

	/**
	 * Merge the additional configured providers into the configuration.
	 *
	 * @param \Illuminate\Foundation\Application $app
	 *
	 * @throws BindingResolutionException
	 */
    protected function mergeAdditionalProviders(Application $app):void
    {
        if (static::$bootstrapProviderPath &&
            file_exists(static::$bootstrapProviderPath)) {
            $packageProviders = require static::$bootstrapProviderPath;

            foreach ($packageProviders as $index => $provider) {
                if (! class_exists($provider)) {
                    unset($packageProviders[$index]);
                }
            }
        }

        $app->make('config')->set(
            'app.providers',
            array_merge(
                $app->make('config')->get('app.providers') ?? (new DefaultProviders( $this->defaultProviders ))->toArray(),
                static::$merge,
                array_values($packageProviders ?? []),
            ),
        );
    }
}
