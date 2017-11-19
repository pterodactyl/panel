<?php

namespace Pterodactyl\Transformers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\ApiTransformer;

class UserTransformer extends ApiTransformer
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['servers'];

    /**
     * Setup request object for transformer.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
     * @return bool|\League\Fractal\Resource\Collection
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function includeServers(User $user)
    {
        if ($this->authorize('server-list')) {
            return false;
        }

        if (! $user->relationLoaded('servers')) {
            $user->load('servers');
        }

        return $this->collection($user->getRelation('servers'), new ServerTransformer($this->request), 'server');
    }
}
