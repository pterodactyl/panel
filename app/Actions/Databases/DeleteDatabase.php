<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Databases;

use Exception;
use Pterodactyl\Contracts\Databases\DeletesDatabases;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Models\Database;
use Pterodactyl\Services\Databases\DatabaseHostGateway;

final readonly class DeleteDatabase implements DeletesDatabases
{
    public function __construct(
        private DynamicDatabaseConnection $dynamic,
        private DatabaseHostGateway $gateway,
    ) {}

    /** @throws Exception */
    public function delete(Database $database): ?bool
    {
        $this->dynamic->set('dynamic', $database->relationLoaded('host') ? $database->host : $database->database_host_id);

        $this->gateway->dropDatabase($database->database);
        $this->gateway->dropUser($database->username, $database->remote);
        $this->gateway->flush();

        return $database->delete();
    }
}
