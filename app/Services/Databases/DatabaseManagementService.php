<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Databases;

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

            $this->repository->createDatabase($database->database);
            $this->repository->createUser(
                $database->username,
                $database->remote,
                $this->encrypter->decrypt($database->password)
            );
            $this->repository->assignUserToDatabase(
                $database->database,
                $database->username,
                $database->remote
            );
            $this->repository->flush();

            $this->database->commit();
        } catch (\Exception $ex) {
            try {
                if (isset($database)) {
                    $this->repository->dropDatabase($database->database);
                    $this->repository->dropUser($database->username, $database->remote);
                    $this->repository->flush();
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
     * Delete a database from the given host server.
     *
     * @param int $id
     * @return bool|null
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete($id)
    {
        $database = $this->repository->find($id);
        $this->dynamic->set('dynamic', $database->database_host_id);

        $this->repository->dropDatabase($database->database);
        $this->repository->dropUser($database->username, $database->remote);
        $this->repository->flush();

        return $this->repository->delete($id);
    }
}
