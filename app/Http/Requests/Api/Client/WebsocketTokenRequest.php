<?php

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Models\Permission;
use Illuminate\Auth\Access\AuthorizationException;

class WebsocketTokenRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_WEBSOCKET_CONNECT;
    }

    protected function failedAuthorization()
    {
        throw new AuthorizationException('You do not have permission to connect to this server\'s websocket.');
    }
}
