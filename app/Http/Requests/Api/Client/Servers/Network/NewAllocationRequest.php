<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Network;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class NewAllocationRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission(): string
    {
        return Permission::ACTION_ALLOCATION_CREATE;
    }
}
