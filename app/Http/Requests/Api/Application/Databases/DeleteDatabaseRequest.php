<?php

namespace Pterodactyl\Http\Requests\Api\Application\Databases;

use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteDatabaseRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_DATABASE_HOSTS;
    protected int $permission = AdminAcl::WRITE;

    public function resourceExists(): bool
    {
        $databaseHost = $this->route()->parameter('databaseHost');

        return $databaseHost instanceof DatabaseHost && $databaseHost->exists;
    }
}
