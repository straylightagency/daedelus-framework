<?php

namespace Daedelus\Foundation\Cache;

use Illuminate\Cache\Repository;
use Psr\SimpleCache\InvalidArgumentException;

/**
 *
 */
class WordPressProxyCache
{

    /** @var array Global cache groups */
    protected array $globalGroups = [];

    /** @var array Global non persistent groups */
    protected array $nonPersistentGroups = [];

    /** @var string Default group name if none defined */
    protected string $defaultGroup = 'default';

    /**
     * Constructor.
     */
    public function __construct(protected Repository $store, protected bool $multisite = false, protected string $blogPrefix = '')
    {
    }

    /**
     * Sets the list of global cache groups.
     *
     * @param array $groups
     */
    public function addGlobalGroups(array $groups): void
    {
        $groups = array_fill_keys( $groups, true );

        $this->globalGroups = array_merge(
            $this->globalGroups,
            $groups,
        );
    }

    /**
     * Adds a group or set of groups to the list of non-persistent groups.
     *
     * @param array $groups
     */
    public function addNonPersistentGroups(array $groups): void
    {
        $this->nonPersistentGroups = array_unique(
            array_merge(
                $this->nonPersistentGroups,
                $groups,
            ),
        );
    }

    /**
     * Switches the internal blog prefix ID.
     *
     * @param int $blog_id
     */
    public function switchToBlog(int $blog_id): void
    {
        $this->blogPrefix = $this->multisite ? $blog_id . ':' : '';
    }

    /**
     * Format key name based on a key and a group.
     * WordPress cache keys are stored using a nomenclature
     * in their name: groupname_keyname
     *
     * @param string $key
     * @param string $group
     *
     * @return string
     */
    private function formatKeyName(string $key, string $group): string
    {
        return sprintf('%s_%s', $group, $key);
    }

	/**
	 * Retrieves the cache contents, if it exists.
	 *
	 * @param int|string $key
	 * @param string $group
	 * @param bool $force
	 * @param null $found
	 *
	 * @return bool|mixed False on failure. Cache value on success.
	 * @throws InvalidArgumentException
	 */
    public function get(int|string $key, string $group = 'default', bool $force = false, &$found = null): mixed
    {
        $group = $group ?: $this->defaultGroup;

        if ( $this->multisite && !isset( $this->globalGroups[ $group ] ) ) {
            $key = $this->blogPrefix . $key;
        }

        $formatted_key = $this->formatKeyName( $key, $group );

        $found = $this->store->has( $formatted_key );

		if ( $group === 'site-transient' && !$found ) {
			$no_timeout = [ 'update_core', 'update_plugins', 'update_themes' ];
			$transient_option = '_site_transient_' . $key;

			if ( ! in_array( $key, $no_timeout, true ) ) {
				$transient_timeout = '_site_transient_timeout_' . $key;
				wp_prime_site_option_caches( [ $transient_option, $transient_timeout ] );

				$timeout = get_site_option( $transient_timeout );

				if ( false !== $timeout && $timeout < time() ) {
					delete_site_option( $transient_option );
					delete_site_option( $transient_timeout );
					$value = false;
				}
			}

			if ( ! isset( $value ) ) {
				$value = get_site_option( $transient_option );
			}

			$this->store->set( $formatted_key, $value, 300 );
		}

        return $this->store->get( $formatted_key, false );
    }

    /**
     * Store an item into the cache.
     *
     * @param string $key
     * @param mixed $data
     * @param string|null $group
     * @param int $expire
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function set(string $key, mixed $data, ?string $group = 'default', int $expire = 0): bool
    {
        $group = $group ?: $this->defaultGroup;

        if ( $this->multisite && !isset( $this->globalGroups[ $group ] ) ) {
            $key = $this->blogPrefix . $key;
        }

        return $this->store->set( $this->formatKeyName( $key, $group ), $data, $expire );
    }

    /**
     * Adds data to the cache if the cache key doesn't already exist.
     *
     * @param string $key
     * @param mixed $data
     * @param string|null $group
     * @param int $expire
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function add(string $key, mixed $data, ?string $group = 'default', int $expire = 0): bool
    {
        if ( function_exists('wp_suspend_cache_addition') && wp_suspend_cache_addition() ) {
            return false;
        }

        $group = $group ?: $this->defaultGroup;

        if ( $this->multisite && !isset( $this->globalGroups[ $group ] ) ) {
            $key = $this->blogPrefix . $key;
        }

        $key = $this->formatKeyName( $key, $group );

        return !$this->store->has( $key ) && $this->set( $key, $data, $group, $expire );
    }

    /**
     * Decrement numeric cache item's value.
     *
     * @param string $key
     * @param int $offset
     * @param string|null $group
     *
     * @return bool|int
     */
    public function decrement(string $key, int $offset = 1, ?string $group = 'default'): bool|int
    {
        $group = $group ?: $this->defaultGroup;

        if ( $this->multisite && ! isset( $this->globalGroups[ $group ] ) ) {
            $key = $this->blogPrefix . $key;
        }

        $key = $this->formatKeyName( $key, $group );

        return $this->store->has( $key ) && $this->store->decrement( $key, $offset );
    }

    /**
     * Increment numeric cache item's value.
     *
     * @param string $key
     * @param int $offset
     * @param string|null $group
     *
     * @return bool|int
     */
    public function increment(string $key, int $offset = 1, ?string $group = 'default'): bool|int
    {
        $group = $group ?: $this->defaultGroup;

        if ( $this->multisite && !isset( $this->globalGroups[ $group ] ) ) {
            $key = $this->blogPrefix . $key;
        }

        $key = $this->formatKeyName( $key, $group );

        if ( !$this->store->has( $key ) ) {
            return false;
        }

        return $this->store->has( $key ) && $this->store->increment( $key, $offset );
    }

    /**
     * Removes the cache contents matching key.
     *
     * @param string $key
     * @param string|null $group
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function delete(string $key, ?string $group = 'default'): bool
    {
        $group = $group ?: $this->defaultGroup;

        if ( $this->multisite && !isset( $this->globalGroups[ $group ] ) ) {
            $key = $this->blogPrefix . $key;
        }

        return $this->store->delete(
            $this->formatKeyName( $key, $group )
        );
    }

    /**
     * Replaces the content in the cache, if content already exists.
     *
     * @param string $key
     * @param mixed $data
     * @param string|null $group
     * @param int $expire
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function replace(string $key, mixed $data, ?string $group = 'default', int $expire = 0): bool
    {
        $group = $group ?: $this->defaultGroup;

        if ( $this->multisite && ! isset( $this->globalGroups[ $group ] ) ) {
            $key = $this->blogPrefix . $key;
        }

        $key = $this->formatKeyName( $key, $group );

        if ( !$this->store->has( $key ) ) {
            return false;
        }

        return $this->set( $key, $data, $group, $expire );
    }

    /**
     * Removes all cache items.
     *
     * @return bool
     */
    public function flush(): bool
    {
        return $this->store->clear();
    }
}