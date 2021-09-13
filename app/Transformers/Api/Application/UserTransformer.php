<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class UserTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['role', 'servers'];

    public function getResourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    public function transform(User $model): array
    {
        return [
            'id' => $model->id,
            'external_id' => $model->external_id,
            'uuid' => $model->uuid,
            'username' => $model->username,
            'email' => $model->email,
            'language' => $model->language,
            'root_admin' => (bool) $model->root_admin,
            '2fa' => (bool) $model->use_totp,
            'avatar_url' => $model->avatarURL(),
            'admin_role_id' => $model->admin_role_id,
            'role_name' => $model->adminRoleName(),
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Return the role associated with this user.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeRole(User $user)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_ROLES) || is_null($user->adminRole)) {
            return $this->null();
        }

        return $this->item($user->adminRole, new AdminRoleTransformer());
    }

    /**
     * Return the servers associated with this user.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeServers(User $user)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($user->servers, new ServerTransformer());
    }
}
