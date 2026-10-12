<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Themes;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetThemesRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminThemesRead];
    }
}
