<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Network;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class GetNetworkRequest extends ClientApiRequest
{
    /**
     * Check that the user has permission to view the allocations for
     * this server.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('view-allocations', $this->getModel(Server::class));
    }
}
