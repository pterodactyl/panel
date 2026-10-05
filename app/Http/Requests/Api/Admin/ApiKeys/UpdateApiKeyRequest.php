<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\ApiKeys;

use Pterodactyl\Enum\Permissions;

class UpdateApiKeyRequest extends StoreApiKeyRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminApiKeysUpdate];
    }

    // Same payload as creation; rules and getKeyPermissions() are inherited.
}
