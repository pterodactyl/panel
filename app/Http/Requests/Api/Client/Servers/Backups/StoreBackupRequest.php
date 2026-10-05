<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Illuminate\Support\Arr;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Support\JsonValueGuard;

class StoreBackupRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permissions::BackupCreate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:191'],
            'is_locked' => ['nullable', 'boolean'],
            'ignored' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array{name: string|null, is_locked: bool, ignored: string|null}
     */
    public function payload(): array
    {
        $data = parent::validated();

        return [
            'name' => JsonValueGuard::nullableString(Arr::get($data, 'name')),
            'is_locked' => JsonValueGuard::boolean(Arr::get($data, 'is_locked', false)),
            'ignored' => JsonValueGuard::nullableString(Arr::get($data, 'ignored')),
        ];
    }
}
