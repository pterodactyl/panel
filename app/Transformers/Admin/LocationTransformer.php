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
use Pterodactyl\Models\Location;
use League\Fractal\TransformerAbstract;

class LocationTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
        'nodes',
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
     * Return a generic transformed pack array.
     *
     * @param Location $location
     * @return array
     */
    public function transform(Location $location)
    {
        return $location->toArray();
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param Location $location
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeServers(Location $location)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-list')) {
            return;
        }

        return $this->collection($location->servers, new ServerTransformer($this->request), 'server');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param Location $location
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeNodes(Location $location)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('node-list')) {
            return;
        }

        return $this->collection($location->nodes, new NodeTransformer($this->request), 'node');
    }
}
