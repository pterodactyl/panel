<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Api;

use Pterodactyl\Contracts\Api\UpdatesApiKeys;
use Pterodactyl\Models\ApiKey;

final readonly class UpdateApiKey implements UpdatesApiKeys
{
    /**
     * Update an API key's attributes without regenerating its token.
     *
     * @param  ApiKeyUpdateData  $data
     * @param  array<string, int>  $permissions  The r_* ACL columns, only applied to application keys.
     */
    public function update(ApiKey $key, array $data, array $permissions = []): ApiKey
    {
        if ($key->key_type === ApiKey::TYPE_APPLICATION) {
            $data = array_merge($data, $permissions);
        }

        $key->forceFill($data)->saveOrFail();

        return $key;
    }
}
