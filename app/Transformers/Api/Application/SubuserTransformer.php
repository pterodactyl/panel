<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class SubuserTransformer extends BaseTransformer
{
    /**
     * List of resources that can be included.
     */
    protected array $availableIncludes = ['user', 'server'];

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
    public function transform(Subuser $subuser): array
    {
        return [
            'id' => $subuser->id,
            'user_id' => $subuser->user_id,
            'server_id' => $subuser->server_id,
            'permissions' => $subuser->permissions,
            'created_at' => $this->formatTimestamp($subuser->created_at),
            'updated_at' => $this->formatTimestamp($subuser->updated_at),
        ];
    }

    /**
     * Return a generic item of user for this subuser.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeUser(Subuser $subuser)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_USERS)) {
            return $this->null();
        }

        $subuser->loadMissing('user');

        return $this->item($subuser->getRelation('user'), $this->makeTransformer(UserTransformer::class), 'user');
    }

    /**
     * Return a generic item of server for this subuser.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeServer(Subuser $subuser)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $subuser->loadMissing('server');

        return $this->item($subuser->getRelation('server'), $this->makeTransformer(ServerTransformer::class), 'server');
    }
}
