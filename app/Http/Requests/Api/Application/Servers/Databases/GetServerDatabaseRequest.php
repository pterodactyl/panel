<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers\Databases;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetServerDatabaseRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_SERVER_DATABASES;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the requested server database exists.
     */
    public function resourceExists(): bool
    {
        $server = $this->route()->parameter('server');
        $database = $this->route()->parameter('database');

        return $database->server_id === $server->id;
    }
}
