<?php

namespace Pterodactyl\Services\Databases;

use Exception;
use Pterodactyl\Models\Database;
use Illuminate\Support\Facades\Log;
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
     * @return string
     *
     * @throws \Throwable
     */
    public function handle(Database $database): string
    {
        $password = str_random(24);
        // Given a random string of characters, randomly loop through the characters and replace some
        // with special characters to avoid issues with MySQL password requirements on some servers.
        try {
            for ($i = 0; $i < random_int(2, 6); $i++) {
                $character = ['!', '@', '=', '.', '+', '^'][random_int(0, 5)];

                $password = substr_replace($password, $character, random_int(0, 23), 1);
            }
        } catch (Exception $exception) {
            // Just log the error and hope for the best at this point.
            Log::error($exception);
        }

        $this->connection->transaction(function () use ($database, $password) {
            $this->dynamic->set('dynamic', $database->database_host_id);

            $this->repository->withoutFreshModel()->update($database->id, [
                'password' => $this->encrypter->encrypt($password),
            ]);

            $this->repository->dropUser($database->username, $database->remote);
            $this->repository->createUser($database->username, $database->remote, $password);
            $this->repository->assignUserToDatabase($database->database, $database->username, $database->remote);
            $this->repository->flush();
        });

        return $password;
    }
}
