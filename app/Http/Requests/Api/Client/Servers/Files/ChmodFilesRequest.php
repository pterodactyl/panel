<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class ChmodFilesRequest extends ClientApiRequest implements ClientPermissionsRequest
{
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
            'files.*.file' => ['required', 'string', 'max:2048'],
            'files.*.mode' => ['required', 'regex:/^[0-7]{3,4}$/'],
        ];
    }
}
