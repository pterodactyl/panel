<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\User;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class UserTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = ['servers'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    /**
     * Return a transformed User model that can be consumed by external services.
     */
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
            'avatar_url' => $model->avatar_url,
            'admin_role_id' => $model->admin_role_id,
            'role_name' => $model->admin_role_name,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Return the servers associated with this user.
     */
    public function includeServers(User $user): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->collection($user->servers, new ServerTransformer());
    }
}
