<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
     * @param  \Illuminate\Http\Request|bool  $request
     * @return void
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
     * @return array
     */
    public function transform(Node $node)
    {
        return $node->toArray();
    }

    /**
     * Return the nodes associated with this location.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeAllocations(Node $node)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('view-node')) {
            return;
        }

        return $this->collection($node->allocations, new AllocationTransformer, 'allocation');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includeLocation(Node $node)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('view-node')) {
            return;
        }

        return $this->item($node->location, new LocationTransformer, 'location');
    }

    /**
     * Return the nodes associated with this location.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeServers(Node $node)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('list-servers')) {
            return;
        }

        return $this->collection($node->servers, new ServerTransformer, 'server');
    }
}
