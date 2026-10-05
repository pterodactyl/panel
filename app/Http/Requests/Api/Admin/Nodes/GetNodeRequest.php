<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes;

use Pterodactyl\Enum\Permissions;

class GetNodeRequest extends GetNodesRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminNodesRead];
    }
}
