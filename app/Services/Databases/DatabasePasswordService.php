<?php

namespace Pterodactyl\Services\Databases;

use Pterodactyl\Models\Database;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabasePasswordService
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
     * DatabasePasswordService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                      $connection
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $repository
     * @param \Pterodactyl\Extensions\DynamicDatabaseConnection             $dynamic
     * @param \Illuminate\Contracts\Encryption\Encrypter                    $encrypter
     */
    public function __construct(
        ConnectionInterface $connection,
        DatabaseRepositoryInterface $repository,
        DynamicDatabaseConnection $dynamic,
        Encrypter $encrypter
    ) {
        $this->connection = $connection;
        $this->dynamic = $dynamic;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Updates a password for a given database.
     *
     * @param \Pterodactyl\Models\Database|int $database
     * @param string                           $password
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($database, string $password): bool
    {
        if (! $database instanceof Database) {
            $database = $this->repository->find($database);
        }

        $this->dynamic->set('dynamic', $database->database_host_id);
        $this->connection->beginTransaction();

        $updated = $this->repository->withoutFreshModel()->update($database->id, [
            'password' => $this->encrypter->encrypt($password),
        ]);

        $this->repository->dropUser($database->username, $database->remote);
        $this->repository->createUser($database->username, $database->remote, $password);
        $this->repository->assignUserToDatabase($database->database, $database->username, $database->remote);
        $this->repository->flush();

        unset($password);
        $this->connection->commit();

        return $updated;
    }
}
