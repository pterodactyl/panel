<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\AuditLogs;

use Pterodactyl\Models\Permission;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class GetAuditLogsRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_AUDITLOGS_READ;
    }
}
