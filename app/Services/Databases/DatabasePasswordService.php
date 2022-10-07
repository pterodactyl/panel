<?php

namespace Pterodactyl\Services\Databases;

use Pterodactyl\Models\Database;
use Pterodactyl\Helpers\Utilities;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabasePasswordService
{
    private ConnectionInterface $connection;

    private DynamicDatabaseConnection $dynamic;

    private Encrypter $encrypter;

    private DatabaseRepositoryInterface $repository;

    /**
     * DatabasePasswordService constructor.
     */
    public function __construct(
        ConnectionInterface $connection,
        DynamicDatabaseConnection $dynamic,
        Encrypter $encrypter,
        DatabaseRepositoryInterface $repository
    ) {
        $this->connection = $connection;
        $this->dynamic = $dynamic;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Updates a password for a given database.
     *
     * @throws \Throwable
     */
    public function handle(Database|int $database): string
    {
        $password = Utilities::randomStringWithSpecialCharacters(24);

        $this->connection->transaction(function () use ($database, $password) {
            $this->dynamic->set('dynamic', $database->database_host_id);

            $this->repository->withoutFreshModel()->update($database->id, [
                'password' => $this->encrypter->encrypt($password),
            ]);

            $this->repository->dropUser($database->username, $database->remote);
            $this->repository->createUser($database->username, $database->remote, $password, $database->max_connections);
            $this->repository->assignUserToDatabase($database->database, $database->username, $database->remote);
            $this->repository->flush();
        });

        return $password;
    }
}
