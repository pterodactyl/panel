<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Pterodactyl\Actions\Backups\RestoreBackup;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class RestoreBackupRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permissions::BackupRestore->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return RestoreBackup::rules();
    }
}
