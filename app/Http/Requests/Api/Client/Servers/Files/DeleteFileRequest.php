<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Files;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DeleteFileRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permissions::FileDelete->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'root' => ['required', 'nullable', 'string'],
            'files' => ['required', 'array'],
            'files.*' => ['string'],
        ];
    }
}
