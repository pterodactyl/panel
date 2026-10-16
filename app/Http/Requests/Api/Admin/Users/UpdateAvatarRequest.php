<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Users;

use Pterodactyl\Enum\Permissions;

class UpdateAvatarRequest extends StoreUserRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminUsersUpdate];
    }

    public function rules(?array $rules = null): array
    {
        return ['avatar' => ['required', 'file', 'max:10240']];
    }
}
