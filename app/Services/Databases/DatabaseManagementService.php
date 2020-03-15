<?php

namespace Pterodactyl\Services\Databases;

use Pterodactyl\Models\Database;
use Pterodactyl\Helpers\Utilities;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabaseManagementService
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    private $database;

    /**
     * @var \Pterodactyl\Extensions\DynamicDatabaseConnection
     */
    private $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * @var bool
     */
    protected $useRandomHost = false;

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
     * @return \Pterodactyl\Models\Database
     *
     * @throws \Exception
     */
    public function create($server, array $data)
    {
        $data['server_id'] = $server;
        $data['database'] = sprintf('s%d_%s', $server, $data['database']);
        $data['username'] = sprintf('u%d_%s', $server, str_random(10));
        $data['password'] = $this->encrypter->encrypt(
            Utilities::randomStringWithSpecialCharacters(24)
        );

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
                if (isset($database) && $database instanceof Database) {
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
