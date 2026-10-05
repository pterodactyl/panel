<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetDatabaseHostRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminDatabaseHostsRead];
    }
}
