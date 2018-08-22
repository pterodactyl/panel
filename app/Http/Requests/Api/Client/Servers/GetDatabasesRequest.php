<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class GetDatabasesRequest extends ClientApiRequest
{
    /**
     * Determine if this user has permission to view all of the databases available
     * to this server.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('view-databases', $this->getModel(Server::class));
    }
}
