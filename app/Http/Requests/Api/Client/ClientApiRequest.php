<?php

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\ApiRequest;

abstract class ClientApiRequest extends ApiRequest
{
    /**
     * Returns the permissions string indicating which permission should be used to
     * validate that the authenticated user has permission to perform this action against
     * the given resource (server).
     */
    abstract public function permission(): string;

    /**
     * Determine if the current user is authorized to perform the requested action
     * against the API.
     */
    public function authorize(): bool
    {
        $server = $this->route()->parameter('server');

        if ($server instanceof Server) {
            return $this->user()->can($this->permission(), $server);
        }

        // If there is no server available on the reqest, trigger a failure since
        // we expect there to be one at this point.
        return false;
    }
}
