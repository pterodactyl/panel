<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ServerPropertiesRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return 'properties.manage';
    }
}
