<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Tags;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetTagsRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminTagsRead];
    }
}
