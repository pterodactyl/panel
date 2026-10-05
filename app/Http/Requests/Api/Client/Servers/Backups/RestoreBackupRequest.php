<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Illuminate\Support\Arr;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Support\JsonValueGuard;

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
        $data = parent::validated();

        return [
            'truncate' => JsonValueGuard::boolean(Arr::get($data, 'truncate')),
        ];
    }
}
