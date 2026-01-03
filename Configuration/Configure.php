<?php
namespace Daedelus\Framework\Configuration;

use RuntimeException;

/**
 *
 *
 * @author Anthony Pauwels <anthony@straylightagency.be>
 * @package Majestic
 */
class Configure
{
    /** @var array<string, mixed> */
    protected array $items = [];

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return Configure
     * @throws RuntimeException
     */
    public function define(string $key, mixed $value):static
    {
        $this->defined( $key ) or $this->items[ $key ] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws RuntimeException
     */
    public function get(string $key):mixed
    {
        if ( !array_key_exists( $key, $this->items )) {
            throw new RuntimeException( sprintf( 'Item at key ` %s ` has not been defined. Use define method before.', $key ) );
        }

        return $this->items[ $key ];
    }

    /**
     * @param string $key
     */
    public function remove(string $key):void
    {
        unset( $this->items[ $key ] );
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    public function apply():void
    {
        foreach ( $this->items as $key => $value ) {
            try {
                $this->defined( $key );
            } catch ( RuntimeException $e ) {
                if ( constant( $key ) !== $value ) {
                    throw $e;
                }
            }
        }

        foreach ( $this->items as $key => $value ) {
            defined( $key ) or define( $key, $value );
        }
    }

    /**
     * @param string $key
     * @return false
     * @throws RuntimeException
     */
    protected function defined(string $key):bool
    {
        if ( defined( $key ) ) {
            throw new RuntimeException( sprintf('Trying to redefine constant "%s".', $key ) );
        }

        return false;
    }
}