<?php

namespace Pterodactyl\Http\Requests\Api\Application\Eggs;

use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteEggRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_EGGS;
    protected int $permission = AdminAcl::WRITE;

    public function resourceExists(): bool
    {
        $egg = $this->route()->parameter('egg');

        return $egg instanceof Egg && $egg->exists;
    }
}
