<?php

if ( !defined('ABSPATH') ) {
	die();
}

/**
 * @return void
 */
function wp_cache_init(): void
{
	$GLOBALS['wp_object_cache'] = wp_cache();
}

/**
 * Adds data to the cache if the cache key doesn't already exist.
 *
 * @param string|int $key
 * @param mixed $data
 * @param string $group
 * @param int $expire
 *
 * @return bool True on success. False if cache value is already in the store.
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function wp_cache_add(int|string $key, mixed $data, string $group = '', int $expire = 0): bool
{
    global $wp_object_cache;

    return $wp_object_cache->add($key, $data, $group, $expire);
}

/**
 * Closes the cache.
 *
 * @return bool
 */
function wp_cache_close(): bool
{
    return true;
}

/**
 * Decrement numeric cache item's value.
 *
 * @param int|string $key
 * @param int $offset
 * @param string $group
 *
 * @return bool|int False on failure. The item's new value on success.
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function wp_cache_decr(int|string $key, int $offset = 1, string $group = ''): bool|int
{
    global $wp_object_cache;

    return $wp_object_cache->decrement($key, $offset, $group);
}

/**
 * Increment numeric cache item's value.
 *
 * @param int|string $key
 * @param int $offset
 * @param string $group
 *
 * @return bool|int False on failure. The item's new value on success.
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function wp_cache_incr(int|string $key, int $offset = 1, string $group = ''): bool|int
{
    global $wp_object_cache;

    return $wp_object_cache->increment($key, $offset, $group);
}

/**
 * Removes the cache contents matching key.
 *
 * @param int|string $key
 * @param string $group
 *
 * @return bool True on success. False on failure.
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function wp_cache_delete(int|string $key, string $group = ''): bool
{
    global $wp_object_cache;

    return $wp_object_cache->delete($key, $group);
}

/**
 * Removes all cache items.
 *
 * @return bool True on success. False on failure.
 */
function wp_cache_flush(): bool
{
    global $wp_object_cache;

    return $wp_object_cache->flush();
}

/**
 * Retrieve the cache content from the cache by key.
 *
 * @param int|string $key
 * @param string $group
 * @param bool $force
 * @param null $found
 *
 * @return mixed False on failure. Cache content on success.
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function wp_cache_get(int|string $key, string $group = '', bool $force = false, &$found = null): mixed
{
    global $wp_object_cache;

    return $wp_object_cache->get($key, $group, $force, $found);
}

/**
 * Store an item in the cache.
 *
 * @param string|int $key
 * @param mixed $data
 * @param string $group
 * @param int $expire
 *
 * @return bool False on failure. True on success.
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function wp_cache_set(int|string $key, mixed $data, string $group = '', int $expire = 0): bool
{
    global $wp_object_cache;

    return $wp_object_cache->set($key, $data, $group, $expire);
}

/**
 * Replaces the content of the cache with new data.
 *
 * @param int|string $key
 * @param mixed $data
 * @param string $group
 * @param int $expire
 *
 * @return bool False if original value does not exist. True if replaced.
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function wp_cache_replace(int|string $key, mixed $data, string $group = '', int $expire = 0): bool
{
    global $wp_object_cache;

    return $wp_object_cache->replace($key, $data, $group, $expire);
}

/**
 * Switches the internal blog ID (prefix).
 *
 * @param int|string $blog_id
 */
function wp_cache_switch_to_blog(int|string $blog_id): void
{
    global $wp_object_cache;

    $wp_object_cache->switchToBlog((int) $blog_id);
}

/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @param array|string $groups
 */
function wp_cache_add_global_groups(array|string $groups): void
{
    global $wp_object_cache;

    $wp_object_cache->addGlobalGroups($groups);
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @param array|string $groups
 */
function wp_cache_add_non_persistent_groups(array|string $groups): void
{
    global $wp_object_cache;

    $wp_object_cache->addNonPersistentGroups((array) $groups);
}

/**
 * Reset internal cache keys and structures.
 *
 * @return bool
 */
function wp_cache_reset(): bool
{
    _deprecated_function( __FUNCTION__, '3.5.0', 'wp_cache_switch_to_blog()' );

    return true;
}

/**
 * @param string $feature
 *
 * @return bool
 */
function wp_cache_supports(string $feature): bool
{
    return match ($feature) {
        'add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple', 'flush_runtime', 'flush_group' => true,
        default => false,
    };
}