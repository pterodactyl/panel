<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Worlds;

use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SearchWorldsRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_READ;
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:120',
            'minecraft_version' => 'nullable|string|max:32',
            'page' => 'nullable|integer|min:1|max:1000',
            'page_size' => 'nullable|integer|min:1|max:25',
            'sort' => 'nullable|string|in:featured,downloads',
        ];
    }
}
