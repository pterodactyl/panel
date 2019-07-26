<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Databases;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class GetDatabasesRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * @return string
     */
    public function permission(): string
    {
        return 'view-databases';
    }
}
