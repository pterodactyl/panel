<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Database;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabaseRepository extends EloquentRepository implements DatabaseRepositoryInterface
{
    /**
     * @var string
     */
    protected $connection = self::DEFAULT_CONNECTION_NAME;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * DatabaseRepository constructor.
     */
    public function __construct(Application $application, DatabaseManager $database)
    {
        parent::__construct($application);

        $this->database = $database;
    }

    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Database::class;
    }

    /**
     * Set the connection name to execute statements against.
     *
     * @return $this
     */
    public function setConnection(string $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Return the connection to execute statements against.
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * Return all of the databases belonging to a server.
     */
    public function getDatabasesForServer(int $server): Collection
    {
        return $this->getBuilder()->with('host')->where('server_id', $server)->get($this->getColumns());
    }

    /**
     * Return all of the databases for a given host with the server relationship loaded.
     */
    public function getDatabasesForHost(int $host, int $count = 25): LengthAwarePaginator
    {
        return $this->getBuilder()->with('server')
            ->where('database_host_id', $host)
            ->paginate($count, $this->getColumns());
    }

    /**
     * Create a new database on a given connection.
     */
    public function createDatabase(string $database): bool
    {
        return $this->run(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $database));
    }

    /**
     * Create a new database user on a given connection.
     *
     * @param $max_connections
     */
    public function createUser(string $username, string $remote, string $password, $max_connections): bool
    {
        if (!$max_connections) {
            return $this->run(sprintf('CREATE USER `%s`@`%s` IDENTIFIED BY \'%s\'', $username, $remote, $password));
        } else {
            return $this->run(sprintf('CREATE USER `%s`@`%s` IDENTIFIED BY \'%s\' WITH MAX_USER_CONNECTIONS %s', $username, $remote, $password, $max_connections));
        }
    }

    /**
     * Give a specific user access to a given database.
     */
    public function assignUserToDatabase(string $database, string $username, string $remote): bool
    {
        return $this->run(sprintf(
            'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, REFERENCES, INDEX, LOCK TABLES, CREATE ROUTINE, ALTER ROUTINE, EXECUTE, CREATE TEMPORARY TABLES, CREATE VIEW, SHOW VIEW, EVENT, TRIGGER ON `%s`.* TO `%s`@`%s`',
            $database,
            $username,
            $remote
        ));
    }

    /**
     * Flush the privileges for a given connection.
     */
    public function flush(): bool
    {
        return $this->run('FLUSH PRIVILEGES');
    }

    /**
     * Drop a given database on a specific connection.
     */
    public function dropDatabase(string $database): bool
    {
        return $this->run(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
    }

    /**
     * Drop a given user on a specific connection.
     *
     * @return mixed
     */
    public function dropUser(string $username, string $remote): bool
    {
        return $this->run(sprintf('DROP USER IF EXISTS `%s`@`%s`', $username, $remote));
    }

    /**
     * Run the provided statement against the database on a given connection.
     */
    private function run(string $statement): bool
    {
        return $this->database->connection($this->getConnection())->statement($statement);
    }
}
