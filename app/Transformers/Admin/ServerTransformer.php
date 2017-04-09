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
use Pterodactyl\Models\Server;
use League\Fractal\TransformerAbstract;

class ServerTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
        'allocations',
        'user',
        'subusers',
        'pack',
        'service',
        'option',
        'variables',
        'location',
        'node',
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
     * Return a generic transformed server array.
     *
     * @return array
     */
    public function transform(Server $server)
    {
        return collect($server->toArray())->only($server->getTableColumns())->toArray();
    }

    /**
     * Return a generic array of allocations for this server.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeAllocations(Server $server)
    {
        return $this->collection($server->allocations, new AllocationTransformer($this->request, 'server'), 'allocation');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeSubusers(Server $server)
    {
        return $this->collection($server->subusers, new SubuserTransformer($this->request), 'subuser');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includeUser(Server $server)
    {
        return $this->item($server->user, new UserTransformer($this->request), 'user');
    }

    /**
     * Return a generic array with pack information for this server.
     *
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includePack(Server $server)
    {
        return $this->item($server->pack, new PackTransformer($this->request), 'pack');
    }

    /**
     * Return a generic array with service information for this server.
     *
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includeService(Server $server)
    {
        return $this->item($server->service, new ServiceTransformer($this->request), 'service');
    }

    /**
     * Return a generic array with service option information for this server.
     *
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includeOption(Server $server)
    {
        return $this->item($server->option, new OptionTransformer($this->request), 'option');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeVariables(Server $server)
    {
        return $this->collection($server->variables, new ServerVariableTransformer($this->request), 'server_variable');
    }

    /**
     * Return a generic array with pack information for this server.
     *
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includeLocation(Server $server)
    {
        return $this->item($server->location, new LocationTransformer($this->request), 'location');
    }

    /**
     * Return a generic array with pack information for this server.
     *
     * @return \Leauge\Fractal\Resource\Item|void
     */
    public function includeNode(Server $server)
    {
        return $this->item($server->node, new NodeTransformer($this->request), 'node');
    }
}
