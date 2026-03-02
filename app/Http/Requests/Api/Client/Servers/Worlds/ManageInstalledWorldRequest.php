<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Worlds;

use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ManageInstalledWorldRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_DELETE;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|min:3|max:191',
        ];
    }
}
