<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\UserSSHKey;

class UserSSHKeyTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return UserSSHKey::RESOURCE_NAME;
    }

    /**
     * Return's a user's SSH key in an API response format.
     */
    public function transform(UserSSHKey $model): array
    {
        return [
            'name' => $model->name,
            'fingerprint' => $model->fingerprint,
            'public_key' => $model->public_key,
            'created_at' => $model->created_at->toAtomString(),
        ];
    }
}
