<?php
namespace Daedelus\Foundation;

use Daedelus\Foundation\Hooks\Hook;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;

/**
 *
 */
class HooksRepository
{
	/** @var array */
	protected array $hooks = [];

	/**
	 * @param Application $app
	 */
	public function __construct(protected Application $app)
	{
	}

	/**
	 * @param array $hooks
	 *
	 * @return $this
	 */
	public function addHooks(array $hooks):self
	{
		$this->hooks = array_merge( $this->hooks, $hooks );

		return $this;
	}

	/**
	 * @throws BindingResolutionException
	 */
	public function register():void
	{
		$this->registerHooks( $this->hooks );
	}

	/**
	 * @param array $hooks
	 *
	 * @return void
	 * @throws BindingResolutionException
	 */
	protected function registerHooks(array $hooks):void
	{
		collect( $hooks )
			->reject( fn ( $hook ) => ( $hook instanceof Hook ) )
			->map( fn ( $hook ) => $this->app->make( $hook ) )
            ->each( function (Hook $hook) {
                $this->registerHooks( $hook->hooks() );

                $hook->condition() && $hook->register();
            } );
	}
}