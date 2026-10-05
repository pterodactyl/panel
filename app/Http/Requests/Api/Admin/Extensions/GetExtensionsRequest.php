<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Extensions;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetExtensionsRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminExtensionsRead];
    }
}
