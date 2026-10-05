<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Enum\Permissions;

class UpdateServerSuspensionRequest extends ServerWriteRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServersUpdate];
    }

    /**
     * Validation rules for toggling a server's suspension.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'suspended' => ['nullable', 'boolean'],
        ];
    }
}
