<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Network;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SetPrimaryAllocationRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission(): string
    {
        return Permission::ACTION_ALLOCIATION_UPDATE;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'ip' => 'required|string',
            'port' => 'required|numeric|min:1024|max:65535',
        ];
    }
}
