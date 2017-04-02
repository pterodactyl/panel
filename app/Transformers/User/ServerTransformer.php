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

namespace Pterodactyl\Transformers\User;

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
        'subusers',
        'stats',
    ];

    /**
     * Return a generic transformed server array.
     *
     * @return array
     */
    public function transform(Server $server)
    {
        return [
            'uuidShort' => $server->uuidShort,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'description' => $server->description,
            'node' => $server->node->name,
            'limits' => [
                'memory' => $server->memory,
                'swap' => $server->swap,
                'disk' => $server->disk,
                'io' => $server->io,
                'cpu' => $server->cpu,
                'oom_disabled' => (bool) $server->oom_disabled,
            ],
        ];
    }

    /**
     * Return a generic array of allocations for this server.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeAllocations(Server $server)
    {
        $allocations = $server->allocations;

        return $this->collection($allocations, new AllocationTransformer($server));
    }

    /**
     * Return a generic array of subusers for this server.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeSubusers(Server $server)
    {
        $server->load('subusers.permissions', 'subusers.user');

        return $this->collection($server->subusers, new SubuserTransformer);
    }

    /**
     * Return a generic array of allocations for this server.
     *
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includeStats(Server $server)
    {
        return $this->item($server->guzzleClient(), new StatsTransformer);
    }
}
