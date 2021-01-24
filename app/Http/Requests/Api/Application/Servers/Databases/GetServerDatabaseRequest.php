<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers\Databases;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetServerDatabaseRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_SERVER_DATABASES;
    protected int $permission = AdminAcl::READ;

    public function resourceExists(): bool
    {
        $server = $this->route()->parameter('server');
        $database = $this->route()->parameter('database');

        return $database->server_id === $server->id;
    }
}
