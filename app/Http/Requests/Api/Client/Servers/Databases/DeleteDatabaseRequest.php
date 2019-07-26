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
     * @return bool
     */
    public function resourceExists(): bool
    {
        return $this->getModel(Server::class)->id === $this->getModel(Database::class)->server_id;
    }
}
