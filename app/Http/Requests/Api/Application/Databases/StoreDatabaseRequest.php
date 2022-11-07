<?php

namespace Pterodactyl\Http\Requests\Api\Application\Databases;

use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreDatabaseRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_DATABASE_HOSTS;

    protected int $permission = AdminAcl::WRITE;

    public function rules(array $rules = null): array
    {
        return $rules ?? DatabaseHost::getRules();
    }
}
