<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\SecurityKey;
use Pterodactyl\Transformers\Api\Transformer;

class SecurityKeyTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return SecurityKey::RESOURCE_NAME;
    }

    public function transform(SecurityKey $key): array
    {
        return [
            'uuid' => $key->uuid,
            'name' => $key->name,
            'type' => $key->type,
            'public_key_id' => base64_encode($key->public_key_id),
            'created_at' => self::formatTimestamp($key->created_at),
            'updated_at' => self::formatTimestamp($key->updated_at),
        ];
    }
}
