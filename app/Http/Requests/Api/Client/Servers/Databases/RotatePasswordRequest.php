<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Databases;

use Pterodactyl\Models\Server;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class RotatePasswordRequest extends ClientApiRequest
{
    /**
     * Check that the user has permission to rotate the password.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('reset-db-password', $this->getModel(Server::class));
    }
}
