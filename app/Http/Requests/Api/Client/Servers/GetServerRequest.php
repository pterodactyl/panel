<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class GetServerRequest extends ClientApiRequest
{
    /**
     * Determine if a client has permission to view this server on the API. This
     * should never be false since this would be checking the same permission as
     * resourceExists().
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Determine if the user should even know that this server exists.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        return $this->user()->can('view-server', $this->getModel(Server::class));
    }
}
