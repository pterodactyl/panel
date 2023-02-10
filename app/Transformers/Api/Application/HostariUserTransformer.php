<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\User;

class HostariUserTransformer extends UserTransformer
{
    /**
     * Return a transformed User model that can be consumed by external services.
     */
    public function transform(User $model): array
    {
        $token = $model->createToken('hostari token', []);

        return [
            'id' => $model->id,
            'external_id' => $model->external_id,
            'uuid' => $model->uuid,
            'username' => $model->username,
            'email' => $model->email,
            'language' => $model->language,
            'root_admin' => (bool) $model->root_admin,
            '2fa' => (bool) $model->use_totp,
            'avatar_url' => $model->avatar_url,
            'admin_role_id' => $model->admin_role_id,
            'role_name' => $model->admin_role_name,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
            'token' => $token->plainTextToken,
        ];
    }
}
