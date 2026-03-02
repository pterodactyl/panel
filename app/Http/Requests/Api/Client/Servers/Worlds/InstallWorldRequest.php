<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Worlds;

use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class InstallWorldRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_CREATE;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|integer|min:1',
            'file_id' => 'required|integer|min:1',
            'name' => 'required|string|min:3|max:191',
            'minecraft_version' => 'required|string|max:32',
            'activate_after_install' => 'nullable|boolean',
            'overwrite_existing' => 'nullable|boolean',
        ];
    }
}
