<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Databases;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DeleteDatabaseRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * @return string
     */
    public function permission(): string
    {
        return 'delete-database';
    }

    /**
     * Determine if the provided database even belongs to this server instance.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $server = $this->getModel(Server::class);
        $database = $this->getModel(Database::class);

        return $database->server_id === $server->id;
    }
}
