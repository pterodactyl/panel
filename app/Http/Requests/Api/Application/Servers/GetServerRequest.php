<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetServerRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_SERVERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the requested server exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $server = $this->route()->parameter('server');

        return $server instanceof Server && $server->exists;
    }
}
