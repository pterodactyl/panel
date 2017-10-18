<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

interface DatabaseRepositoryInterface extends RepositoryInterface
{
    /**
     * Create a new database if it does not already exist on the host with
     * the provided details.
     *
     * @param array $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function createIfNotExists(array $data);

    /**
     * Create a new database on a given connection.
     *
     * @param string      $database
     * @param null|string $connection
     * @return bool
     */
    public function createDatabase($database, $connection = null);

    /**
     * Create a new database user on a given connection.
     *
     * @param string      $username
     * @param string      $remote
     * @param string      $password
     * @param null|string $connection
     * @return bool
     */
    public function createUser($username, $remote, $password, $connection = null);

    /**
     * Give a specific user access to a given database.
     *
     * @param string      $database
     * @param string      $username
     * @param string      $remote
     * @param null|string $connection
     * @return bool
     */
    public function assignUserToDatabase($database, $username, $remote, $connection = null);

    /**
     * Flush the privileges for a given connection.
     *
     * @param null|string $connection
     * @return mixed
     */
    public function flush($connection = null);

    /**
     * Drop a given database on a specific connection.
     *
     * @param string      $database
     * @param null|string $connection
     * @return bool
     */
    public function dropDatabase($database, $connection = null);

    /**
     * Drop a given user on a specific connection.
     *
     * @param string      $username
     * @param string      $remote
     * @param null|string $connection
     * @return mixed
     */
    public function dropUser($username, $remote, $connection = null);
}
