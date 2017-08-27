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
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabaseManagementService
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var \Pterodactyl\Extensions\DynamicDatabaseConnection
     */
    protected $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $repository;

    /**
     * CreationService constructor.
     *
     * @param \Illuminate\Database\DatabaseManager                          $database
     * @param \Pterodactyl\Extensions\DynamicDatabaseConnection             $dynamic
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $repository
     * @param \Illuminate\Contracts\Encryption\Encrypter                    $encrypter
     */
    public function __construct(
        DatabaseManager $database,
        DynamicDatabaseConnection $dynamic,
        DatabaseRepositoryInterface $repository,
        Encrypter $encrypter
    ) {
        $this->database = $database;
        $this->dynamic = $dynamic;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Create a new database that is linked to a specific host.
     *
     * @param int   $server
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create($server, array $data)
    {
        $data['server_id'] = $server;
        $data['database'] = sprintf('d%d_%s', $server, $data['database']);
        $data['username'] = sprintf('u%d_%s', $server, str_random(10));
        $data['password'] = $this->encrypter->encrypt(str_random(16));

        $this->database->beginTransaction();
        try {
            $database = $this->repository->createIfNotExists($data);
            $this->dynamic->set('dynamic', $data['database_host_id']);

            $this->repository->createDatabase($database->database, 'dynamic');
            $this->repository->createUser(
                $database->username,
                $database->remote,
                $this->encrypter->decrypt($database->password),
                'dynamic'
            );
            $this->repository->assignUserToDatabase(
                $database->database,
                $database->username,
                $database->remote,
                'dynamic'
            );
            $this->repository->flush('dynamic');

            $this->database->commit();
        } catch (\Exception $ex) {
            try {
                if (isset($database)) {
                    $this->repository->dropDatabase($database->database, 'dynamic');
                    $this->repository->dropUser($database->username, $database->remote, 'dynamic');
                    $this->repository->flush('dynamic');
                }
            } catch (\Exception $exTwo) {
                // ignore an exception
            }

            $this->database->rollBack();
            throw $ex;
        }

        return $database;
    }

    /**
     * Change the password for a specific user and database combination.
     *
     * @param int    $id
     * @param string $password
     * @return bool
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function changePassword($id, $password)
    {
        $database = $this->repository->find($id);
        $this->dynamic->set('dynamic', $database->database_host_id);

        $this->database->beginTransaction();

        try {
            $updated = $this->repository->withoutFresh()->update($id, [
                'password' => $this->encrypter->encrypt($password),
            ]);

            $this->repository->dropUser($database->username, $database->remote, 'dynamic');
            $this->repository->createUser($database->username, $database->remote, $password, 'dynamic');
            $this->repository->assignUserToDatabase(
                $database->database,
                $database->username,
                $database->remote,
                'dynamic'
            );
            $this->repository->flush('dynamic');

            $this->database->commit();
        } catch (\Exception $ex) {
            $this->database->rollBack();
            throw $ex;
        }

        return $updated;
    }

    /**
     * Delete a database from the given host server.
     *
     * @param int $id
     * @return bool|null
     */
    public function delete($id)
    {
        $database = $this->repository->find($id);
        $this->dynamic->set('dynamic', $database->database_host_id);

        $this->repository->dropDatabase($database->database, 'dynamic');
        $this->repository->dropUser($database->username, $database->remote, 'dynamic');
        $this->repository->flush('dynamic');

        return $this->repository->delete($id);
    }
}
