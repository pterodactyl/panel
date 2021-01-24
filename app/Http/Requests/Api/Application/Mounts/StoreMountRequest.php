<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Models\Mount;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreMountRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_MOUNTS;
    protected int $permission = AdminAcl::WRITE;

    public function rules(array $rules = null): array
    {
        return $rules ?? Mount::getRules();
    }
}
