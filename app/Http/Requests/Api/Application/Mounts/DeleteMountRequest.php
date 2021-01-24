<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Models\Mount;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteMountRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_MOUNTS;
    protected int $permission = AdminAcl::WRITE;

    public function resourceExists(): bool
    {
        $mount = $this->route()->parameter('mount');

        return $mount instanceof Mount && $mount->exists;
    }
}
