<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nests;

use Pterodactyl\Models\Nest;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteNestRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_NESTS;
    protected int $permission = AdminAcl::WRITE;

    public function resourceExists(): bool
    {
        $nest = $this->route()->parameter('nest');

        return $nest instanceof Nest && $nest->exists;
    }
}
