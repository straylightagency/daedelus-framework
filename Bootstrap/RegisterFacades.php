<?php

namespace Daedelus\Foundation\Bootstrap;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Facade;

class RegisterFacades
{
	/**
	 * The facades that should be merged before registration.
	 *
	 * @var array
	 */
	protected static array $merge = [];

	/**
	 * Bootstrap the given application.
	 *
	 * @param \Illuminate\Contracts\Foundation\Application $app
	 *
	 * @return void
	 * @throws BindingResolutionException
	 */
    public function bootstrap(Application $app):void
    {
        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($app);

        AliasLoader::getInstance( array_merge(
            $app->make('config')->get('app.aliases') ?? Facade::defaultAliases()->toArray(),
	        static::$merge,
            $app->make(PackageManifest::class)->aliases()
        ) )->register();
    }

	/**
	 * Merge the given facades into the configuration before registration.
	 *
	 * @param  array  $facades
	 * @return void
	 */
	public static function merge(array $facades):void
	{
		static::$merge = array_values( array_filter( array_unique(
			array_merge( static::$merge, $facades )
		) ) );
	}
}
