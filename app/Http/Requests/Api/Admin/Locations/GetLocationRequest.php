<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Locations;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetLocationRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminLocationsRead];
    }
}
