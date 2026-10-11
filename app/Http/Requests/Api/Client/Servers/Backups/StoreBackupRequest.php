<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Pterodactyl\Actions\Backups\InitiateBackup;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class StoreBackupRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permissions::BackupCreate->value;
    }

    /**
     * The backup's name and lock take the action's rules. The files to ignore are sent as
     * one string, a file or folder per line, which ignoredFiles() splits into the list the
     * action takes.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = InitiateBackup::rules();

        return [
            'name' => $rules['name'],
            'is_locked' => $rules['is_locked'],
            'ignored' => ['nullable', 'string'],
        ];
    }

    /**
     * The files and folders to leave out of the backup, one per line of the ignored field.
     *
     * @return list<string>
     */
    public function ignoredFiles(): array
    {
        return explode(PHP_EOL, $this->safe()->string('ignored')->toString());
    }
}
