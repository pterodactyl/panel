<?php

namespace Pterodactyl\Http\Requests\Api\Application\Servers\Databases;

use Pterodactyl\Services\Acl\Api\AdminAcl;

class ServerDatabaseWriteRequest extends GetServerDatabasesRequest
{
    protected int $permission = AdminAcl::WRITE;
}
