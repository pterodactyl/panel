<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Users\UserAvatarService;

/**
 * @extends ClientResource<User>
 */
class AccountResource extends ClientResource
{
    public function resourceName(): string
    {
        return 'user';
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'admin' => $this->resource->root_admin,
            'username' => $this->resource->username,
            'email' => $this->resource->email,
            'first_name' => $this->resource->name_first,
            'last_name' => $this->resource->name_last,
            'language' => $this->resource->language,
            'avatar' => resolve(UserAvatarService::class)->url($this->resource->avatar),
        ];
    }
}
