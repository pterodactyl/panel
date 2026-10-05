<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Tags;

use Pterodactyl\Enum\Permissions;

class GetTagRequest extends GetTagsRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminTagsRead];
    }
}
