<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Transformer;

class SubuserTransformer extends Transformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['user', 'server'];

    public function getResourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    public function transform(Subuser $subuser): array
    {
        return [
            'id' => $subuser->id,
            'user_id' => $subuser->user_id,
            'server_id' => $subuser->server_id,
            'permissions' => $subuser->permissions,
            'created_at' => self::formatTimestamp($subuser->created_at),
            'updated_at' => self::formatTimestamp($subuser->updated_at),
        ];
    }

    /**
     * Return a generic item of user for this subuser.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeUser(Subuser $subuser)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        return $this->item($subuser->user, new UserTransformer());
    }

    /**
     * Return a generic item of server for this subuser.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeServer(Subuser $subuser)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        return $this->item($subuser->server, new ServerTransformer());
    }
}
