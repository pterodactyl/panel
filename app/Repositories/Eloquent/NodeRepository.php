<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Node;
use Pterodactyl\Repositories\Concerns\Searchable;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class NodeRepository extends EloquentRepository implements NodeRepositoryInterface
{
    use Searchable;

    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Node::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsageStats($id)
    {
        $node = $this->getBuilder()->select(
            'nodes.disk_overallocate',
            'nodes.memory_overallocate',
            'nodes.disk',
            'nodes.memory',
            $this->getBuilder()->raw('SUM(servers.memory) as sum_memory, SUM(servers.disk) as sum_disk')
        )->join('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('nodes.id', $id)
            ->first();

        return collect(['disk' => $node->sum_disk, 'memory' => $node->sum_memory])
            ->mapWithKeys(function ($value, $key) use ($node) {
                $maxUsage = $node->{$key};
                if ($node->{$key . '_overallocate'} > 0) {
                    $maxUsage = $node->{$key} * (1 + ($node->{$key . '_overallocate'} / 100));
                }

                $percent = ($value / $maxUsage) * 100;

                return [
                    $key => [
                        'value' => number_format($value),
                        'max' => number_format($maxUsage),
                        'percent' => $percent,
                        'css' => ($percent <= 75) ? 'green' : (($percent > 90) ? 'red' : 'yellow'),
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeListingData($count = 25)
    {
        $instance = $this->getBuilder()->with('location')->withCount('servers');

        if ($this->searchTerm) {
            $instance->search($this->searchTerm);
        }

        return $instance->paginate($count, $this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleNode($id)
    {
        $instance = $this->getBuilder()->with('location')->withCount('servers')->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeAllocations($id)
    {
        $instance = $this->getBuilder()->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        $instance->setRelation(
            'allocations',
            $instance->allocations()->orderBy('ip', 'asc')->orderBy('port', 'asc')
                ->with('server')->paginate(50)
        );

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeServers($id)
    {
        $instance = $this->getBuilder()->with('servers.user', 'servers.service', 'servers.option')
            ->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesForLocation($location)
    {
        $instance = $this->getBuilder()->with('allocations')->where('location_id', $location)->get();

        return $instance->map(function ($item) {
            $filtered = $item->allocations->where('server_id', null)->map(function ($map) {
                return collect($map)->only(['id', 'ip', 'port']);
            });

            $item->ports = $filtered->map(function ($map) {
                return [
                    'id' => $map['id'],
                    'text' => sprintf('%s:%s', $map['ip'], $map['port']),
                ];
            })->values();

            return [
                'id' => $item->id,
                'text' => $item->name,
                'allocations' => $item->ports,
            ];
        })->values();
    }
}
