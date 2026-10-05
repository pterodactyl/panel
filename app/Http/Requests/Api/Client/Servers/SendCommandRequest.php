<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class SendCommandRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * Determine if the API user has permission to perform this action.
     */
    public function permission(): string
    {
        return Permissions::ControlConsole->value;
    }

    /**
     * Rules to validate this request against.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'command' => ['required', 'string', 'min:1'],
        ];
    }
}
