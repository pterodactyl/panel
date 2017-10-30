<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Transformers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
        'access',
        'servers',
    ];

    /**
     * The Illuminate Request object if provided.
     *
     * @var \Illuminate\Http\Request|bool
     */
    protected $request;

    /**
     * Setup request object for transformer.
     *
     * @param \Illuminate\Http\Request|bool $request
     */
    public function __construct($request = false)
    {
        if (! $request instanceof Request && $request !== false) {
            throw new DisplayException('Request passed to constructor must be of type Request or false.');
        }

        $this->request = $request;
    }

    /**
     * Return a generic transformed subuser array.
     *
     * @return array
     */
    public function transform(User $user)
    {
        return $user->toArray();
    }

    /**
     * Return the servers associated with this user.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeServers(User $user)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-list')) {
            return;
        }

        return $this->collection($user->servers, new ServerTransformer($this->request), 'server');
    }

    /**
     * Return the servers that this user can access.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeAccess(User $user)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-list')) {
            return;
        }

        return $this->collection($user->access()->get(), new ServerTransformer($this->request), 'server');
    }
}
