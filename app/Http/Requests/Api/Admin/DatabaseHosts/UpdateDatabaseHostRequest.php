<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts;

use Pterodactyl\Enum\Permissions;

class UpdateDatabaseHostRequest extends StoreDatabaseHostRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminDatabaseHostsUpdate];
    }
}
