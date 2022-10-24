<?php

namespace Pterodactyl\Services\Databases;

use Pterodactyl\Models\Database;
use Pterodactyl\Helpers\Utilities;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;

class DatabasePasswordService
{
    /**
     * DatabasePasswordService constructor.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private DynamicDatabaseConnection $dynamic,
        private Encrypter $encrypter
    ) {
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

            $database->update([
                'password' => $this->encrypter->encrypt($password),
            ]);

            $database->dropUser($database->username, $database->remote);
            $database->createUser($database->username, $database->remote, $password, $database->max_connections);
            $database->assignUserToDatabase($database->database, $database->username, $database->remote);
            $database->flush();
        });

        return $password;
    }
}
