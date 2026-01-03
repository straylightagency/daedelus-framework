<?php

namespace Daedelus\Foundation\Configuration;

/**
 *
 */
class Hooks
{
	/**
	 * Constructor.
	 */
	public function __construct(protected array $hooks = [])
	{
	}

	/**
	 * Create a new collection with default hooks
	 *
	 * @return static
	 */
	public static function default():static
	{
		return new static( [
			\Daedelus\Foundation\Hooks\SetupApplication::class,
			\Daedelus\Foundation\Hooks\ConfigMails::class,
			\Daedelus\Foundation\Hooks\CustomizeAdmin::class,
		] );
	}

	/**
	 * Merge the given hooks into the hooks collection.
	 *
	 * @param  array  $hooks
	 * @return static
	 */
	public function merge(array $hooks): static
	{
		return new static( array_merge( $this->hooks, $hooks ) );
	}

	/**
	 * Replace the given hooks with other hooks.
	 *
	 * @param array $replacements
	 *
	 * @return static
	 */
	public function replace(array $replacements): static
	{
		$current = collect( $this->hooks );

		foreach ( $replacements as $from => $to ) {
			$key = $current->search( $from );

			$current = $key ? $current->replace( [ $key => $to ] ) : $current;
		}

		return new static( $current->values()->toArray() );
	}

	/**
	 * Disable the given hooks.
	 *
	 * @param  array  $hooks
	 * @return static
	 */
	public function except(array $hooks): static
	{
		return new static( collect( $this->hooks )
			->reject( fn ( $p ) => in_array( $p, $hooks ) )
			->values()
			->toArray() );
	}

	/**
	 * Convert the hooks collection to an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->hooks;
	}
}