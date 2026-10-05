<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ListFilesRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * Check that the user making this request to the API is authorized to list all
     * the files that exist for a given server.
     */
    public function permission(): string
    {
        return Permissions::FileRead->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'directory' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
