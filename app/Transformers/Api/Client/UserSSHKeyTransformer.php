<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\UserSSHKey;

class UserSSHKeyTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return 'user_ssh_key';
    }

    /**
     * Return basic information about the currently logged in user.
     */
    public function transform(UserSSHKey $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'public_key' => $model->public_key,
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }
}
