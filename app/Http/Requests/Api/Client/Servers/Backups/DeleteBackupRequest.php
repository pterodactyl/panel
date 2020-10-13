<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DeleteBackupRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_BACKUP_DELETE;
    }
}
