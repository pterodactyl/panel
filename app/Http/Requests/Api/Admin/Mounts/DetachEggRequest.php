<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class DetachEggRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminMountsUpdate];
    }
}
