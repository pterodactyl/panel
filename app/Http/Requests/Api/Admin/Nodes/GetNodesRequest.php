<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetNodesRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminNodesRead];
    }
}
