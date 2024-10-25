<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function array_key_exists;

/**
 * Cache values
 */
class Cache
{
    /** @var array<string,mixed> */
    private static $cacheData = [];

    /**
     * Store a value
     *
     * @param string $cacheKey The key to use
     * @param mixed  $value    The value to cache
     */
    public static function set(string $cacheKey, $value): bool
    {
        self::$cacheData[$cacheKey] = $value;

        return true;
    }

    /**
     * Does the cache have a value stored for the key
     *
     * @param string $cacheKey The key to use
     */
    public static function has(string $cacheKey): bool
    {
        return array_key_exists($cacheKey, self::$cacheData);
    }

    /**
     * Get back a cached value
     *
     * @param string $cacheKey     The key to use
     * @param mixed  $defaultValue The default value in case it does not exist
     *
     * @return mixed The cached value
     */
    public static function get(string $cacheKey, $defaultValue = null)
    {
        return self::$cacheData[$cacheKey] ?? $defaultValue;
    }

    /**
     * Remove a cached value
     *
     * @param string $cacheKey The key to use to remove the value
     */
    public static function remove(string $cacheKey): bool
    {
        unset(self::$cacheData[$cacheKey]);

        return true;
    }

    /**
     * Purge all cached values
     */
    public static function purge(): bool
    {
        self::$cacheData = [];

        return self::$cacheData === [];
    }
}
