<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Enum\Permissions;

class StoreServerMountRequest extends ServerWriteRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServerMountsCreate];
    }

    /**
     * Validation rules for attaching a mount to a server.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'mount_id' => ['required', 'integer', 'exists:mounts,id'],
        ];
    }
}
