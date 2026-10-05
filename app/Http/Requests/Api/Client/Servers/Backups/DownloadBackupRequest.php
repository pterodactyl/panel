<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Pterodactyl\Enum\Permissions;

class DownloadBackupRequest extends ViewBackupRequest
{
    public function permission(): string
    {
        return Permissions::BackupDownload->value;
    }
}
