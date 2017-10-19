<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Database;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Exceptions\Repository\DuplicateDatabaseNameException;

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
     *
     * @param \Illuminate\Foundation\Application   $application
     * @param \Illuminate\Database\DatabaseManager $database
     */
    public function __construct(
        Application $application,
        DatabaseManager $database
    ) {
        parent::__construct($application);

        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Database::class;
    }

    /**
     * Set the connection name to execute statements against.
     *
     * @param string $connection
     * @return $this
     */
    public function setConnection(string $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Return the connection to execute statements aganist.
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * Return all of the databases belonging to a server.
     *
     * @param int $server
     * @return \Illuminate\Support\Collection
     */
    public function getDatabasesForServer(int $server): Collection
    {
        return $this->getBuilder()->where('server_id', $server)->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    public function createIfNotExists(array $data)
    {
        $instance = $this->getBuilder()->where([
            ['server_id', '=', array_get($data, 'server_id')],
            ['database_host_id', '=', array_get($data, 'database_host_id')],
            ['database', '=', array_get($data, 'database')],
        ])->count();

        if ($instance > 0) {
            throw new DuplicateDatabaseNameException('A database with those details already exists for the specified server.');
        }

        return $this->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabase($database)
    {
        return $this->runStatement(sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $database));
    }

    /**
     * {@inheritdoc}
     */
    public function createUser($username, $remote, $password)
    {
        return $this->runStatement(sprintf('CREATE USER `%s`@`%s` IDENTIFIED BY \'%s\'', $username, $remote, $password));
    }

    /**
     * {@inheritdoc}
     */
    public function assignUserToDatabase($database, $username, $remote)
    {
        return $this->runStatement(sprintf(
            'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, EXECUTE ON `%s`.* TO `%s`@`%s`',
            $database,
            $username,
            $remote
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return $this->runStatement('FLUSH PRIVILEGES');
    }

    /**
     * {@inheritdoc}
     */
    public function dropDatabase($database)
    {
        return $this->runStatement(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
    }

    /**
     * {@inheritdoc}
     */
    public function dropUser($username, $remote)
    {
        return $this->runStatement(sprintf('DROP USER IF EXISTS `%s`@`%s`', $username, $remote));
    }

    /**
     * Run the provided statement against the database on a given connection.
     *
     * @param string $statement
     * @return bool
     */
    protected function runStatement($statement)
    {
        return $this->database->connection($this->getConnection())->statement($statement);
    }
}
