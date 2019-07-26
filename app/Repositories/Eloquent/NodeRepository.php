<?php

namespace Pterodactyl\Repositories\Eloquent;

use Generator;
use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;
use Pterodactyl\Repositories\Concerns\Searchable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class NodeRepository extends EloquentRepository implements NodeRepositoryInterface
{
    use Searchable;

    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Node::class;
    }

    /**
     * Return the usage stats for a single node.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return array
     */
    public function getUsageStats(Node $node): array
    {
        $stats = $this->getBuilder()->select(
            $this->getBuilder()->raw('IFNULL(SUM(servers.memory), 0) as sum_memory, IFNULL(SUM(servers.disk), 0) as sum_disk')
        )->join('servers', 'servers.node_id', '=', 'nodes.id')->where('node_id', $node->id)->first();

        return collect(['disk' => $stats->sum_disk, 'memory' => $stats->sum_memory])->mapWithKeys(function ($value, $key) use ($node) {
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
                    'css' => ($percent <= self::THRESHOLD_PERCENTAGE_LOW) ? 'green' : (($percent > self::THRESHOLD_PERCENTAGE_MEDIUM) ? 'red' : 'yellow'),
                ],
            ];
        })->toArray();
    }

    /**
     * Return the usage stats for a single node.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return array
     */
    public function getUsageStatsRaw(Node $node): array
    {
        $stats = $this->getBuilder()->select(
            $this->getBuilder()->raw('IFNULL(SUM(servers.memory), 0) as sum_memory, IFNULL(SUM(servers.disk), 0) as sum_disk')
        )->join('servers', 'servers.node_id', '=', 'nodes.id')->where('node_id', $node->id)->first();

        return collect(['disk' => $stats->sum_disk, 'memory' => $stats->sum_memory])->mapWithKeys(function ($value, $key) use ($node) {
            $maxUsage = $node->{$key};
            if ($node->{$key . '_overallocate'} > 0) {
                $maxUsage = $node->{$key} * (1 + ($node->{$key . '_overallocate'} / 100));
            }

            return [
                $key => [
                    'value' => $value,
                    'max' => $maxUsage,
                ],
            ];
        })->toArray();
    }

    /**
     * Return all available nodes with a searchable interface.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNodeListingData(): LengthAwarePaginator
    {
        $instance = $this->getBuilder()->with('location')->withCount('servers');

        if ($this->hasSearchTerm()) {
            $instance->search($this->getSearchTerm());
        }

        return $instance->paginate(25, $this->getColumns());
    }

    /**
     * Return a single node with location and server information.
     *
     * @param \Pterodactyl\Models\Node $node
     * @param bool                     $refresh
     * @return \Pterodactyl\Models\Node
     */
    public function loadLocationAndServerCount(Node $node, bool $refresh = false): Node
    {
        if (! $node->relationLoaded('location') || $refresh) {
            $node->load('location');
        }

        // This is quite ugly and can probably be improved down the road.
        // And by probably, I mean it should.
        if (is_null($node->servers_count) || $refresh) {
            $node->load('servers');
            $node->setRelation('servers_count', count($node->getRelation('servers')));
            unset($node->servers);
        }

        return $node;
    }

    /**
     * Attach a paginated set of allocations to a node mode including
     * any servers that are also attached to those allocations.
     *
     * @param \Pterodactyl\Models\Node $node
     * @param bool                     $refresh
     * @return \Pterodactyl\Models\Node
     */
    public function loadNodeAllocations(Node $node, bool $refresh = false): Node
    {
        $node->setRelation('allocations',
            $node->allocations()->orderByRaw('server_id IS NOT NULL DESC, server_id IS NULL')->orderByRaw('INET_ATON(ip) ASC')->orderBy('port', 'asc')->with('server:id,name')->paginate(50)
        );

        return $node;
    }

    /**
     * Return a collection of nodes for all locations to use in server creation UI.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getNodesForServerCreation(): Collection
    {
        return $this->getBuilder()->with('allocations')->get()->map(function (Node $item) {
            $filtered = $item->getRelation('allocations')->where('server_id', null)->map(function ($map) {
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

    /**
     * Return the IDs of all nodes that exist in the provided locations and have the space
     * available to support the additional disk and memory provided.
     *
     * @param array $locations
     * @param int   $disk
     * @param int   $memory
     * @return \Generator
     */
    public function getNodesWithResourceUse(array $locations, int $disk, int $memory): Generator
    {
        $instance = $this->getBuilder()
            ->select(['nodes.id', 'nodes.memory', 'nodes.disk', 'nodes.memory_overallocate', 'nodes.disk_overallocate'])
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory, IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->leftJoin('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('nodes.public', 1);

        if (! empty($locations)) {
            $instance->whereIn('nodes.location_id', $locations);
        }

        return $instance->groupBy('nodes.id')->cursor();
    }
}
