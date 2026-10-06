<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class PullFileRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permissions::FileCreate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url', 'max:2048'],
            'directory' => ['nullable', 'string', 'max:2048'],
            'filename' => ['nullable', 'string', 'max:255'],
            'use_header' => ['boolean'],
            'foreground' => ['boolean'],
        ];
    }
}
