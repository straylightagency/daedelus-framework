<?php

namespace Daedelus\Foundation\Cache;

use Illuminate\Cache\CacheServiceProvider as BaseCacheServiceProvider;

/**
 *
 */
class CacheServiceProvider extends BaseCacheServiceProvider
{
	/**
	 * @return void
	 */
	public function register(): void
	{
		parent::register();

		$this->app->singleton(WordPressProxyCache::class, fn ($app) => new WordPressProxyCache(
			$app['cache.store'],
			$is_multisite = is_multisite(),
			$is_multisite ? get_current_blog_id() . ':' : ''
		) );
	}
}