<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;

/**
 * @method User user($guard = null)
 */
class ClientApiRequest extends ApplicationApiRequest
{
    /**
     * Determine if the current user is authorized to perform the requested action against the API.
     */
    public function authorize(): bool
    {
        if ($this instanceof ClientPermissionsRequest) {
            $server = $this->parameter('server', Server::class);

            return $this->user()->can($this->permission(), $server);
        }

        return true;
    }
}
