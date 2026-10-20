<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Users\UserAvatarService;

/**
 * @extends ClientResource<User>
 */
class UserResource extends ClientResource
{
    public function resourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'identifier' => $this->resource->identifier,
            'username' => $this->resource->username,
            'email' => $this->resource->email,
            'image' => app(UserAvatarService::class)->url($this->resource->avatar) ?? 'https://gravatar.com/avatar/'.md5(Str::lower($this->resource->email)),
            'avatar' => app(UserAvatarService::class)->url($this->resource->avatar),
            '2fa_enabled' => $this->resource->use_totp,
            'created_at' => $this->formatTimestamp($this->resource->created_at),
        ];
    }
}
