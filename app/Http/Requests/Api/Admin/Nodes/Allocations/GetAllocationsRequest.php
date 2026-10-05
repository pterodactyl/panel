<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetAllocationsRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminAllocationsRead];
    }
}
