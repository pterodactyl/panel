<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Transformer;

class UserTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    /**
     * Transforms a User model into a representation that can be shown to regular
     * users of the API.
     */
    public function transform(User $model): array
    {
        return [
            'uuid' => $model->uuid,
            'username' => $model->username,
            'email' => $model->email,
            'image' => $model->avatar_url,
            '2fa_enabled' => $model->use_totp,
            'created_at' => self::formatTimestamp($model->created_at),
        ];
    }
}
