<?php

declare(strict_types=1);

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
     *
     * @return ApiPayload
     */
    public function transform(UserSSHKey $model): array
    {
        return [
            'name' => $model->name,
            'fingerprint' => $model->fingerprint,
            'public_key' => $model->public_key,
            'created_at' => $this->formatTimestamp($model->created_at),
        ];
    }
}
