<?php

namespace Pterodactyl\Transformers\Api\Client;

use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Transformer;

class UserTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    public function transform(User $model): array
    {
        return [
            'uuid' => $model->uuid,
            'username' => $model->username,
            'email' => $model->email,
            'image' => 'https://gravatar.com/avatar/' . md5(Str::lower($model->email)),
            '2fa_enabled' => $model->use_totp,
            'created_at' => self::formatTimestamp($model->created_at),
        ];
    }
}
