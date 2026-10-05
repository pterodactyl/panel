<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Databases;

use Illuminate\Database\DatabaseManager;

/**
 * Runs the administrative statements (databases, users, grants) against a
 * database host over the connection DynamicDatabaseConnection configures.
 */
class DatabaseHostGateway
{
    public const string DEFAULT_CONNECTION_NAME = 'dynamic';

    private string $connection = self::DEFAULT_CONNECTION_NAME;

    public function __construct(private readonly DatabaseManager $database) {}

    /**
     * Set the connection name to execute statements against.
     */
    public function setConnection(string $connection): self
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
     * Create a new database on the host.
     */
    public function createDatabase(string $database): bool
    {
        return $this->run(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $database));
    }

    /**
     * Create a new database user on the host.
     */
    public function createUser(string $username, string $remote, string $password, ?int $maxConnections): bool
    {
        $args = [$username, $remote, $password];
        $command = "CREATE USER `%s`@`%s` IDENTIFIED BY '%s'";

        if (! empty($maxConnections)) {
            $args[] = $maxConnections;
            $command .= ' WITH MAX_USER_CONNECTIONS %s';
        }

        return $this->run(sprintf($command, ...$args));
    }

    /**
     * Give a user access to a database.
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
     * Flush the host's privilege tables.
     */
    public function flush(): bool
    {
        return $this->run('FLUSH PRIVILEGES');
    }

    /**
     * Drop a database on the host.
     */
    public function dropDatabase(string $database): bool
    {
        return $this->run(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
    }

    /**
     * Drop a user on the host.
     */
    public function dropUser(string $username, string $remote): bool
    {
        return $this->run(sprintf('DROP USER IF EXISTS `%s`@`%s`', $username, $remote));
    }

    private function run(string $statement): bool
    {
        return $this->database->connection($this->getConnection())->statement($statement);
    }
}
