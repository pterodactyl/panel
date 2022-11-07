<?php

namespace Pterodactyl\Http\Requests\Api\Application\Databases;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteDatabaseRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_DATABASE_HOSTS;

    protected int $permission = AdminAcl::WRITE;
}
