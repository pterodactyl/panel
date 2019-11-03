<?php

namespace Pterodactyl\Transformers\Api\Client;

use Illuminate\Support\Str;
use Pterodactyl\Models\Subuser;

class SubuserTransformer extends BaseClientTransformer
{
    protected $availableIncludes = ['permissions'];

    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    /**
     * Transforms a User model into a representation that can be shown to regular
     * users of the API.
     *
     * @param \Pterodactyl\Models\Subuser $model
     * @return array
     */
    public function transform(Subuser $model)
    {
        $user = $model->user;

        return [
            'uuid' => $user->uuid,
            'username' => $user->username,
            'email' => $user->email,
            'image' => 'https://gravatar.com/avatar/' . md5(Str::lower($user->email)),
            '2fa_enabled' => $user->use_totp,
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }

    /**
     * Include the permissions associated with this subuser.
     *
     * @param \Pterodactyl\Models\Subuser $model
     * @return \League\Fractal\Resource\Item
     */
    public function includePermissions(Subuser $model)
    {
        return $this->item($model, function (Subuser $model) {
            return ['permissions' => $model->permissions->pluck('permission')];
        });
    }
}
