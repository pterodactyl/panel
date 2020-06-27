<?php

namespace Pterodactyl\Services\Databases;

use Exception;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Helpers\Utilities;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;

class DatabaseManagementService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

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
     * Determines if the service should validate the user's ability to create an additional
     * database for this server. In almost all cases this should be true, but to keep things
     * flexible you can also set it to false and create more databases than the server is
     * allocated.
     *
     * @var bool
     */
    protected $validateDatabaseLimit = true;

    /**
     * CreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param \Pterodactyl\Extensions\DynamicDatabaseConnection $dynamic
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $repository
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     */
    public function __construct(
        ConnectionInterface $connection,
        DynamicDatabaseConnection $dynamic,
        DatabaseRepositoryInterface $repository,
        Encrypter $encrypter
    ) {
        $this->connection = $connection;
        $this->dynamic = $dynamic;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Set wether or not this class should validate that the server has enough slots
     * left before creating the new database.
     *
     * @param bool $validate
     * @return $this
     */
    public function setValidateDatabaseLimit(bool $validate): self
    {
        $this->validateDatabaseLimit = $validate;

        return $this;
    }

    /**
     * Create a new database that is linked to a specific host.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param array $data
     * @return \Pterodactyl\Models\Database
     *
     * @throws \Throwable
     * @throws \Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException
     * @throws \Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function create(Server $server, array $data)
    {
        if (! config('pterodactyl.client_features.databases.enabled')) {
            throw new DatabaseClientFeatureNotEnabledException;
        }

        if ($this->validateDatabaseLimit) {
            // If the server has a limit assigned and we've already reached that limit, throw back
            // an exception and kill the process.
            if (! is_null($server->database_limit) && $server->databases()->count() >= $server->database_limit) {
                throw new TooManyDatabasesException;
            }
        }

        $data = array_merge($data, [
            'server_id' => $server->id,
            'database' => sprintf('s%d_%s', $server->id, $data['database']),
            'username' => sprintf('u%d_%s', $server->id, str_random(10)),
            'password' => $this->encrypter->encrypt(
                Utilities::randomStringWithSpecialCharacters(24)
            ),
        ]);

        $database = null;

        try {
            return $this->connection->transaction(function () use ($data, &$database) {
                $database = $this->repository->createIfNotExists($data);
                $this->dynamic->set('dynamic', $data['database_host_id']);

                $this->repository->createDatabase($database->database);
                $this->repository->createUser(
                    $database->username, $database->remote, $this->encrypter->decrypt($database->password), $database->max_connections
                );
                $this->repository->assignUserToDatabase($database->database, $database->username, $database->remote);
                $this->repository->flush();

                return $database;
            });
        } catch (Exception $exception) {
            try {
                if ($database instanceof Database) {
                    $this->repository->dropDatabase($database->database);
                    $this->repository->dropUser($database->username, $database->remote);
                    $this->repository->flush();
                }
            } catch (Exception $exception) {
                // Do nothing here. We've already encountered an issue before this point so no
                // reason to prioritize this error over the initial one.
            }

            throw $exception;
        }
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
