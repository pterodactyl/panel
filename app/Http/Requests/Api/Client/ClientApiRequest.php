<?php

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

/**
 * @method \Pterodactyl\Models\User user($guard = null)
 */
abstract class ClientApiRequest extends ApplicationApiRequest
{
    /**
     * Determine if the current user is authorized to perform the requested action against the API.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if ($this instanceof ClientPermissionsRequest || method_exists($this, 'permission')) {
            return $this->user()->can($this->permission(), $this->getModel(Server::class));
        }

        return true;
    }
}
