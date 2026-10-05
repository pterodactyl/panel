<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Backups;

use Illuminate\Support\Arr;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Support\JsonValueGuard;

class ListBackupsRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permissions::BackupRead->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array{per_page: int}
     */
    public function payload(): array
    {
        $data = parent::validated();

        $perPage = Arr::get($data, 'per_page', 20);

        return [
            'per_page' => min(JsonValueGuard::integer($perPage), 50),
        ];
    }
}
