<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Transformers\Api\Transformer;

class UserSSHKeyTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return UserSSHKey::RESOURCE_NAME;
    }

    public function transform(UserSSHKey $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'public_key' => $model->public_key,
            'created_at' => self::formatTimestamp($model->created_at),
        ];
    }
}
