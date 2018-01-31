<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class UserTransformer extends BaseTransformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['servers'];

    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return User::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed subuser array.
     *
     * @param \Pterodactyl\Models\User $user
     * @return array
     */
    public function transform(User $user): array
    {
        return $user->toArray();
    }

    /**
     * Return the servers associated with this user.
     *
     * @param \Pterodactyl\Models\User $user
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     */
    public function includeServers(User $user)
    {
        if (! $this->authorize(AdminAcl::RESOURCE_SERVERS)) {
            return $this->null();
        }

        $user->loadMissing('servers');

        return $this->collection($user->getRelation('servers'), $this->makeTransformer(ServerTransformer::class), 'server');
    }
}
