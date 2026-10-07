<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class RenameFileRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * The permission the user is required to have in order to perform this
     * request action.
     */
    public function permission(): string
    {
        return Permissions::FileUpdate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'root' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'files' => ['required', 'array'],
            'files.*' => ['array'],
            'files.*.to' => ['required', 'string', 'max:2048'],
            'files.*.from' => ['required', 'string', 'max:2048'],
        ];
    }
}
