<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers\Backups;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class ListBackupsRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServerBackupsRead];
    }

    // No body; root-admin auth is inherited.
}
