<?php

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Models\Permission;

class WebsocketTokenRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_WEBSOCKET_CONNECT;
    }
}
