<?php

namespace Pterodactyl\Models;

class Setting extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    public static array $validationRules = [
        'key' => 'required|string|between:1,191',
        'value' => 'string',
    ];

    private static array $cache = [];

    private static array $databaseMiss = [];

    /**
     * Store a new persistent setting in the database.
     *
     */
    public static function set(string $key, string $value = null)
    {
        // Clear item from the cache.
        self::clearCache($key);

        self::query()->updateOrCreate(['key' => $key], ['value' => $value ?? '']);

        self::$cache[$key] = $value;
    }

    /**
     * Retrieve a persistent setting from the database.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // If item has already been requested return it from the cache. If
        // we already know it is missing, immediately return the default value.
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        } elseif (array_key_exists($key, self::$databaseMiss)) {
            return value($default);
        }

        $instance = self::query()->where('key', $key)->first();
        if (is_null($instance)) {
            self::$databaseMiss[$key] = true;

            return value($default);
        }

        return self::$cache[$key] = $instance->value;
    }

    /**
     * Remove a key from the database cache.
     */
    public static function forget(string $key)
    {
        self::clearCache($key);

        return self::query()->where('key', $key)->delete();
    }

    /**
     * Remove a key from the cache.
     */
    private static function clearCache(string $key)
    {
        unset(self::$cache[$key], self::$databaseMiss[$key]);
    }
}
