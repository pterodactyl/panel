<?php

namespace Pterodactyl\Contracts\Repository;

interface SettingsRepositoryInterface extends RepositoryInterface
{
    /**
     * Store a new persistent setting in the database.
     *
     * @param string $key
     * @param string $value
     * @return mixed
     */
    public function set(string $key, string $value);

    /**
     * Retrieve a persistent setting from the database.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get(string $key, $default);

    /**
     * Remove a key from the database cache.
     *
     * @param string $key
     * @return mixed
     */
    public function forget(string $key);
}
