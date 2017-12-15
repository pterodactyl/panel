<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Setting;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SettingsRepository extends EloquentRepository implements SettingsRepositoryInterface
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var array
     */
    private $databaseMiss = [];

    /**
     * Return an instance of the model that acts as the base for
     * this repository.
     *
     * @return string
     */
    public function model()
    {
        return Setting::class;
    }

    /**
     * Store a new persistent setting in the database.
     *
     * @param string $key
     * @param string $value
     */
    public function set(string $key, string $value)
    {
        // Clear item from the cache.
        $this->clearCache($key);
        $this->withoutFresh()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Retrieve a persistent setting from the database.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        // If item has already been requested return it from the cache. If
        // we already know it is missing, immediately return the default
        // value.
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        } elseif (array_key_exists($key, $this->databaseMiss)) {
            return $default;
        }

        $instance = $this->getBuilder()->where('key', $key)->first();

        if (is_null($instance)) {
            $this->databaseMiss[$key] = true;

            return $default;
        }

        $this->cache[$key] = $instance->value;

        return $this->cache[$key];
    }

    /**
     * Remove a key from the database cache.
     *
     * @param string $key
     * @return mixed
     */
    public function forget(string $key)
    {
        $this->clearCache($key);
        $this->deleteWhere(['key' => $key]);
    }

    /**
     * Remove a key from the cache.
     *
     * @param string $key
     */
    protected function clearCache(string $key)
    {
        unset($this->cache[$key], $this->databaseMiss[$key]);
    }
}
