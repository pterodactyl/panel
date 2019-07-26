<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class GetFileContentsRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * Returns the permissions string indicating which permission should be used to
     * validate that the authenticated user has permission to perform this action aganist
     * the given resource (server).
     *
     * @return string
     */
    public function permission(): string
    {
        return 'edit-files';
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'file' => 'required|string',
        ];
    }
}
