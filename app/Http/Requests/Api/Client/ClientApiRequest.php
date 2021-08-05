<?php

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

abstract class ClientApiRequest extends ApplicationApiRequest implements ClientPermissionsRequest
{
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
