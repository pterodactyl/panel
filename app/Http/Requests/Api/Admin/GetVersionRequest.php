<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin;

use Pterodactyl\Enum\Permissions;

class GetVersionRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminVersionRead];
    }
}
