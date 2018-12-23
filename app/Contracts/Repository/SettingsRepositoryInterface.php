<?php

namespace Pterodactyl\Contracts\Repository;

interface SettingsRepositoryInterface extends RepositoryInterface
{
    /**
     * Store a new persistent setting in the database.
     *
     * @param string      $key
     * @param string|null $value
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function set(string $key, string $value = null);

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
     */
    public function forget(string $key);
}
