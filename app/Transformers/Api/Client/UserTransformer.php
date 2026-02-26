<?php

namespace Pterodactyl\Transformers\Api\Client;

use Illuminate\Support\Str;
use Pterodactyl\Models\User;

class UserTransformer extends BaseClientTransformer
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
            // used in AccountTransformer. Do we want to keep this?
            'id' => $model->id,
            'uuid' => $model->uuid,
            'identifier' => $model->identifier,
            'username' => $model->username,
            'email' => $model->email,
            'image' => 'https://gravatar.com/avatar/' . md5(Str::lower($model->email)),
            // which do we prefer?
            'admin' => (bool) $user->root_admin,
            'root_admin' => (bool) $user->root_admin,
            '2fa_enabled' => $model->use_totp,
            'first_name' => $model->name_first,
            'last_name' => $model->name_last,
            'language' => $model->language,
            'created_at' => $this->formatTimestamp($user->created_at),
            'updated_at' => $this->formatTimestamp($user->updated_at),
        ];
    }
}
