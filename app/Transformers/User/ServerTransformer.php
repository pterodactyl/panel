<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
     * @param Server $server
     * @return array
     */
    public function transform(Server $server)
    {
        return [
            'id' => $server->uuidShort,
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
     * @param Server $server
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeAllocations(Server $server)
    {
        $allocations = $server->allocations;

        return $this->collection($allocations, new AllocationTransformer($server), 'allocation');
    }

    /**
     * Return a generic array of subusers for this server.
     *
     * @param Server $server
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeSubusers(Server $server)
    {
        $server->load('subusers.permissions', 'subusers.user');

        return $this->collection($server->subusers, new SubuserTransformer, 'subuser');
    }

    /**
     * Return a generic array of allocations for this server.
     *
     * @param Server $server
     * @return \Leauge\Fractal\Resource\Item
     */
    public function includeStats(Server $server)
    {
        return $this->item($server->guzzleClient(), new StatsTransformer, 'stat');
    }
}
