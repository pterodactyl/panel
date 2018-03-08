<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Database;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
     * Return all of the databases for a given host with the server relationship loaded.
     *
     * @param int $host
     * @param int $count
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getDatabasesForHost(int $host, int $count = 25): LengthAwarePaginator;

    /**
     * Create a new database if it does not already exist on the host with
     * the provided details.
     *
     * @param array $data
     * @return \Pterodactyl\Models\Database
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\DuplicateDatabaseNameException
     */
    public function createIfNotExists(array $data): Database;

    /**
     * Create a new database on a given connection.
     *
     * @param string $database
     * @return bool
     */
    public function createDatabase(string $database): bool;

    /**
     * Create a new database user on a given connection.
     *
     * @param string $username
     * @param string $remote
     * @param string $password
     * @return bool
     */
    public function createUser(string $username, string $remote, string $password): bool;

    /**
     * Give a specific user access to a given database.
     *
     * @param string $database
     * @param string $username
     * @param string $remote
     * @return bool
     */
    public function assignUserToDatabase(string $database, string $username, string $remote): bool;

    /**
     * Flush the privileges for a given connection.
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Drop a given database on a specific connection.
     *
     * @param string $database
     * @return bool
     */
    public function dropDatabase(string $database): bool;

    /**
     * Drop a given user on a specific connection.
     *
     * @param string $username
     * @param string $remote
     * @return mixed
     */
    public function dropUser(string $username, string $remote): bool;
}
