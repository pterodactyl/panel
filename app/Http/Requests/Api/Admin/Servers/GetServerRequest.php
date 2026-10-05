<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Enum\Permissions;

class GetServerRequest extends GetServersRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServersRead];
    }
}
