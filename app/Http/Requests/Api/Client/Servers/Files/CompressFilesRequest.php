<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class CompressFilesRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * Checks that the authenticated user is allowed to create archives for this server.
     */
    public function permission(): string
    {
        return Permissions::FileArchive->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'root' => ['sometimes', 'nullable', 'string'],
            'files' => ['required', 'array'],
            'files.*' => ['string'],
        ];
    }
}
