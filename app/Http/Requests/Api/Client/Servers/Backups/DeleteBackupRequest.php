<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Pterodactyl\Enum\Permissions;

class DeleteBackupRequest extends ViewBackupRequest
{
    public function permission(): string
    {
        return Permissions::BackupDelete->value;
    }
}
