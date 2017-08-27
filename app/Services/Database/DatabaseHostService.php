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

namespace Pterodactyl\Services\Database;

use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class DatabaseHostService
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $databaseRepository;

    /**
     * @var \Pterodactyl\Extensions\DynamicDatabaseConnection
     */
    protected $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    protected $repository;

    /**
     * DatabaseHostService constructor.
     *
     * @param \Illuminate\Database\DatabaseManager                              $database
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface     $databaseRepository
     * @param \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface $repository
     * @param \Pterodactyl\Extensions\DynamicDatabaseConnection                 $dynamic
     * @param \Illuminate\Contracts\Encryption\Encrypter                        $encrypter
     */
    public function __construct(
        DatabaseManager $database,
        DatabaseRepositoryInterface $databaseRepository,
        DatabaseHostRepositoryInterface $repository,
        DynamicDatabaseConnection $dynamic,
        Encrypter $encrypter
    ) {
        $this->database = $database;
        $this->databaseRepository = $databaseRepository;
        $this->dynamic = $dynamic;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Create a new database host and persist it to the database.
     *
     * @param array $data
     * @return \Pterodactyl\Models\DatabaseHost
     *
     * @throws \Throwable
     * @throws \PDOException
     */
    public function create(array $data)
    {
        $this->database->beginTransaction();

        $host = $this->repository->create([
            'password' => $this->encrypter->encrypt(array_get($data, 'password')),
            'name' => array_get($data, 'name'),
            'host' => array_get($data, 'host'),
            'port' => array_get($data, 'port'),
            'username' => array_get($data, 'username'),
            'max_databases' => null,
            'node_id' => array_get($data, 'node_id'),
        ]);

        // Check Access
        $this->dynamic->set('dynamic', $host);
        $this->database->connection('dynamic')->select('SELECT 1 FROM dual');

        $this->database->commit();

        return $host;
    }

    /**
     * Update a database host and persist to the database.
     *
     * @param int   $id
     * @param array $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update($id, array $data)
    {
        $this->database->beginTransaction();

        if (! empty(array_get($data, 'password'))) {
            $data['password'] = $this->encrypter->encrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $host = $this->repository->update($id, $data);

        $this->dynamic->set('dynamic', $host);
        $this->database->connection('dynamic')->select('SELECT 1 FROM dual');

        $this->database->commit();

        return $host;
    }

    /**
     * Delete a database host if it has no active databases attached to it.
     *
     * @param int $id
     * @return bool|null
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete($id)
    {
        $count = $this->databaseRepository->findCountWhere([['database_host_id', '=', $id]]);
        if ($count > 0) {
            throw new DisplayException(trans('admin/exceptions.databases.delete_has_databases'));
        }

        return $this->repository->delete($id);
    }
}
