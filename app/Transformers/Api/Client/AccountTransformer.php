<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Transformer;

class AccountTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return 'user';
    }

    /**
     * Return basic information about the currently logged-in user.
     */
    public function transform(User $model): array
    {
        return [
            'id' => $model->id,
            'admin' => $model->root_admin,
            'username' => $model->username,
            'email' => $model->email,
            'language' => $model->language,
        ];
    }
}
