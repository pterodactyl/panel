<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs;

use Pterodactyl\Enum\Permissions;

class UpdateEggRequest extends StoreEggRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggsUpdate];
    }

    // Same contract as create; legacy nest membership remains optionally writable.
}
