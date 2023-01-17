<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Subuser;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class SubuserTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = ['server', 'user'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    /**
     * Return a transformed Subuser model that can be consumed by external services.
     */
    public function transform(Subuser $model): array
    {
        return [
            'id' => $model->id,
            'user_id' => $model->user_id,
            'server_id' => $model->server_id,
            'permissions' => $model->permissions,
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }

    /**
     * Return a generic item of server for this subuser.
     */
    public function includeServer(Subuser $subuser): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->item($subuser->server, new ServerTransformer());
    }

    /**
     * Return a generic item of user for this subuser.
     */
    public function includeUser(Subuser $subuser): Item|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        return $this->item($subuser->user, new UserTransformer());
    }
}
