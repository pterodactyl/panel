<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;

interface DatabaseRepositoryInterface extends RepositoryInterface
{
    const DEFAULT_CONNECTION_NAME = 'dynamic';

    /**
     * Set the connection name to execute statements against.
     *
     * @param string $connection
     * @return $this
     */
    public function setConnection(string $connection);

    /**
     * Return the connection to execute statements aganist.
     *
     * @return string
     */
    public function getConnection(): string;

    /**
     * Return all of the databases belonging to a server.
     *
     * @param int $server
     * @return \Illuminate\Support\Collection
     */
    public function getDatabasesForServer(int $server): Collection;

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
     * @param string $database
     * @return bool
     */
    public function createDatabase($database);

    /**
     * Create a new database user on a given connection.
     *
     * @param string $username
     * @param string $remote
     * @param string $password
     * @return bool
     */
    public function createUser($username, $remote, $password);

    /**
     * Give a specific user access to a given database.
     *
     * @param string $database
     * @param string $username
     * @param string $remote
     * @return bool
     */
    public function assignUserToDatabase($database, $username, $remote);

    /**
     * Flush the privileges for a given connection.
     *
     * @return mixed
     */
    public function flush();

    /**
     * Drop a given database on a specific connection.
     *
     * @param string $database
     * @return bool
     */
    public function dropDatabase($database);

    /**
     * Drop a given user on a specific connection.
     *
     * @param string $username
     * @param string $remote
     * @return mixed
     */
    public function dropUser($username, $remote);
}
