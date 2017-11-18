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
use Pterodactyl\Models\Node;
use League\Fractal\TransformerAbstract;

class NodeTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
        'allocations',
        'location',
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
     * @param Node $node
     * @return array
     */
    public function transform(Node $node)
    {
        return $node->toArray();
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param Node $node
     * @return \League\Fractal\Resource\Collection
     */
    public function includeAllocations(Node $node)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('node-view')) {
            return;
        }

        return $this->collection($node->allocations, new AllocationTransformer($this->request), 'allocation');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param Node $node
     * @return \League\Fractal\Resource\Item
     */
    public function includeLocation(Node $node)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('location-list')) {
            return;
        }

        return $this->item($node->location, new LocationTransformer($this->request), 'location');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @param Node $node
     * @return \League\Fractal\Resource\Collection
     */
    public function includeServers(Node $node)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-list')) {
            return;
        }

        return $this->collection($node->servers, new ServerTransformer($this->request), 'server');
    }
}
