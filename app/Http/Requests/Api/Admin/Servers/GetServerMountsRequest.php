<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Enum\Permissions;

class GetServerMountsRequest extends ServerWriteRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServerMountsRead];
    }
}
