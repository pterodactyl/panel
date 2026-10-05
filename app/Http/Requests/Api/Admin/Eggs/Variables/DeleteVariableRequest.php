<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class DeleteVariableRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggVariablesDelete];
    }
}
