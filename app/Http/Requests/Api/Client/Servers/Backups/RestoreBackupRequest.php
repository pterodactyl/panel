<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

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
        return ['truncate' => ['required', 'boolean']];
    }

    /**
     * @return array{truncate: bool}
     */
    public function payload(): array
    {
        return [
            'truncate' => $this->boolean('truncate'),
        ];
    }
}
