<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class GetEggsRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggsRead];
    }
}
