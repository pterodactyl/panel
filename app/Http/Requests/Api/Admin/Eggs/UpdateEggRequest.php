<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Egg;

class UpdateEggRequest extends StoreEggRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggsUpdate];
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Egg
    {
        return $this->parameter('egg', Egg::class);
    }

    // Same contract as create; legacy nest membership remains optionally writable.
}
