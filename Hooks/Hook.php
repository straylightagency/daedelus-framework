<?php
namespace Daedelus\Foundation\Hooks;

use Closure;

/**
 *
 */
abstract class Hook
{
	/** @var array */
	protected array $hooks = [];

	/**
	 * @param mixed $value
	 *
	 * @return Closure
	 */
	public function scalar(mixed $value):Closure
	{
		return fn () => $value;
	}

	/**
	 * @return Closure
	 */
	public function noop():Closure
	{
		return $this->scalar( false );
	}

	/**
	 * @return Closure
	 */
	public function emptyArray():Closure
	{
		return $this->scalar( [] );
	}

	/**
	 * @return Closure
	 */
	public function zero():Closure
	{
		return $this->scalar( 0 );
	}

    /**
     * @return void
     */
    abstract public function register(): void;

	/**
	 * @return array
	 */
	public function hooks(): array
	{
		return $this->hooks;
	}

	/**
	 * @return bool
	 */
	public function condition(): bool
	{
		return true;
	}
}