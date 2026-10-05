<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Users;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class DisableTwoFactorRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminUsersUpdate];
    }

    // No body; root-admin auth is inherited.
}
