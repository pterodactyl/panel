<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DecompressFilesRequest extends ClientApiRequest
{
    /**
     * Checks that the authenticated user is allowed to create new files for the server. We don't
     * rely on the archive permission here as it makes more sense to make sure the user can create
     * additional files rather than make an archive.
     */
    public function permission(): string
    {
        return Permission::ACTION_FILE_CREATE;
    }

    public function rules(): array
    {
        return [
            'root' => 'sometimes|nullable|string',
            'file' => 'required|string',
        ];
    }
}
