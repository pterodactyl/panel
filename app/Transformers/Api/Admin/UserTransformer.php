<?php

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\ApiTransformer;

class UserTransformer extends ApiTransformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['servers'];

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
     * @return bool|\League\Fractal\Resource\Collection
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function includeServers(User $user)
    {
        if (! $this->authorize('server-list')) {
            return false;
        }

        $user->loadMissing('servers');

        return $this->collection($user->getRelation('servers'), new ServerTransformer($this->getRequest()), 'server');
    }
}
