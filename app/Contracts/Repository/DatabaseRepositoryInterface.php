<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DatabaseRepositoryInterface extends RepositoryInterface
{
    public const DEFAULT_CONNECTION_NAME = 'dynamic';

    /**
     * Set the connection name to execute statements against.
     */
    public function setConnection(string $connection): self;

    /**
     * Return the connection to execute statements against.
     */
    public function getConnection(): string;

    /**
     * Return all the databases belonging to a server.
     */
    public function getDatabasesForServer(int $server): Collection;

    /**
     * Return all the databases for a given host with the server relationship loaded.
     */
    public function getDatabasesForHost(int $host, int $count = 25): LengthAwarePaginator;

    /**
     * Create a new database on a given connection.
     */
    public function createDatabase(string $database): bool;

    /**
     * Create a new database user on a given connection.
     */
    public function createUser(string $username, string $remote, string $password, ?int $max_connections): bool;

    /**
     * Give a specific user access to a given database.
     */
    public function assignUserToDatabase(string $database, string $username, string $remote): bool;

    /**
     * Flush the privileges for a given connection.
     */
    public function flush(): bool;

    /**
     * Drop a given database on a specific connection.
     */
    public function dropDatabase(string $database): bool;

    /**
     * Drop a given user on a specific connection.
     */
    public function dropUser(string $username, string $remote): bool;
}
