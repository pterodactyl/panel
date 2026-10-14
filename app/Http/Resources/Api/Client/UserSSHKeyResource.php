<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Models\UserSSHKey;

/**
 * @extends ClientResource<UserSSHKey>
 */
class UserSSHKeyResource extends ClientResource
{
    public function resourceName(): string
    {
        return UserSSHKey::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'fingerprint' => $this->resource->fingerprint,
            'public_key' => $this->resource->public_key,
            'created_at' => $this->formatTimestamp($this->resource->created_at),
        ];
    }
}
