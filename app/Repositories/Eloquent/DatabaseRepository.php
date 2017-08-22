<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Database;
use Illuminate\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

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
            ['server_id', $data['server_id']],
            ['database_host_id', $data['database_host_id']],
            ['database', $data['database']],
        ])->count();

        if ($instance > 0) {
            throw new DisplayException('A database with those details already exists for the specified server.');
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
                'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX ON `%s`.* TO `%s`@`%s`',
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
