<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\ApiKeys;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetApiKeysRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminApiKeysRead];
    }
}
