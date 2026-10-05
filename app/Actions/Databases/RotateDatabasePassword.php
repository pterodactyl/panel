<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Databases\RotatesDatabasePasswords;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Models\Database;
use Pterodactyl\Services\Databases\DatabaseHostGateway;
use Throwable;

final readonly class RotateDatabasePassword implements RotatesDatabasePasswords
{
    public function __construct(
        private DynamicDatabaseConnection $dynamic,
        private DatabaseHostGateway $gateway,
    ) {}

    /** @throws Throwable */
    public function rotate(Database $database): string
    {
        $password = Utilities::randomStringWithSpecialCharacters(24);

        DB::transaction(function () use ($database, $password): void {
            $database->newQuery()->whereKey($database->getKey())->lockForUpdate()->firstOrFail();
            $database->update(['password' => Crypt::encrypt($password)]);

            $this->dynamic->set('dynamic', $database->database_host_id);
            $this->gateway->dropUser($database->username, $database->remote);
            $this->gateway->createUser($database->username, $database->remote, $password, $database->max_connections);
            $this->gateway->assignUserToDatabase($database->database, $database->username, $database->remote);
            $this->gateway->flush();
        });

        return $password;
    }
}
