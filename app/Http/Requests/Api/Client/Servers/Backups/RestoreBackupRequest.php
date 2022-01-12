<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class RestoreBackupRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_BACKUP_RESTORE;
    }
}
