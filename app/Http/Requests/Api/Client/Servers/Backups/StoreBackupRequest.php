<?php

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Pterodactyl\Models\Permission;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreBackupRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_BACKUP_CREATE;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:191',
            'is_locked' => 'nullable|boolean',
            'ignored' => 'nullable|string',
        ];
    }
}
