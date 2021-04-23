<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Databases;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DeleteDatabaseRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_DATABASE_DELETE;
    }

    public function resourceExists(): bool
    {
        return $this->getModel(Server::class)->id === $this->getModel(Database::class)->server_id;
    }
}
