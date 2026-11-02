<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Users;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class DeleteAvatarRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminUsersUpdate];
    }
}
