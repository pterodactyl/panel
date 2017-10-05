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
use Illuminate\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Exceptions\Repository\DuplicateDatabaseNameException;

class DatabaseRepository extends EloquentRepository implements DatabaseRepositoryInterface
{
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
    public function createDatabase($database, $connection = null)
    {
        return $this->runStatement(
            sprintf('CREATE DATABASE IF NOT EXISTS `%s`', $database),
            $connection
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createUser($username, $remote, $password, $connection = null)
    {
        return $this->runStatement(
            sprintf('CREATE USER `%s`@`%s` IDENTIFIED BY \'%s\'', $username, $remote, $password),
            $connection
        );
    }

    /**
     * {@inheritdoc}
     */
    public function assignUserToDatabase($database, $username, $remote, $connection = null)
    {
        return $this->runStatement(
            sprintf(
                'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, EXECUTE ON `%s`.* TO `%s`@`%s`',
                $database,
                $username,
                $remote
            ),
            $connection
        );
    }

    /**
     * {@inheritdoc}
     */
    public function flush($connection = null)
    {
        return $this->runStatement('FLUSH PRIVILEGES', $connection);
    }

    /**
     * {@inheritdoc}
     */
    public function dropDatabase($database, $connection = null)
    {
        return $this->runStatement(
            sprintf('DROP DATABASE IF EXISTS `%s`', $database),
            $connection
        );
    }

    /**
     * {@inheritdoc}
     */
    public function dropUser($username, $remote, $connection = null)
    {
        return $this->runStatement(
            sprintf('DROP USER IF EXISTS `%s`@`%s`', $username, $remote),
            $connection
        );
    }

    /**
     * Run the provided statement against the database on a given connection.
     *
     * @param string      $statement
     * @param null|string $connection
     * @return bool
     */
    protected function runStatement($statement, $connection = null)
    {
        return $this->database->connection($connection)->statement($statement);
    }
}
