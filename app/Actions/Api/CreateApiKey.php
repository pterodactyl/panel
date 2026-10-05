<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Api;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Api\CreatesApiKeys;
use Pterodactyl\Models\ApiKey;

final readonly class CreateApiKey implements CreatesApiKeys
{
    /**
     * @param  ApiKeyCreationData  $data
     * @param  array<string, int>  $permissions  The r_* ACL columns, only applied to application keys.
     */
    public function create(int $keyType, array $data, array $permissions = []): ApiKey
    {
        $data = array_merge($data, [
            'key_type' => $keyType,
            'identifier' => ApiKey::generateTokenIdentifier($keyType),
            'token' => Crypt::encrypt(Str::random(ApiKey::KEY_LENGTH)),
        ]);

        if ($keyType === ApiKey::TYPE_APPLICATION) {
            $data = array_merge($data, $permissions);
        }

        return ApiKey::query()->forceCreate($data)->refresh();
    }
}
