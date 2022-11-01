<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class NodeRepository extends EloquentRepository implements NodeRepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return Node::class;
    }

    /**
     * Return the usage stats for a single node.
     */
    public function getUsageStats(Node $node): array
    {
        $stats = $node->loadServerSums();

        return Collection::make(['disk' => $stats->sum_disk, 'memory' => $stats->sum_memory])
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
                        'css' => ($percent <= self::THRESHOLD_PERCENTAGE_LOW) ? 'green' : (($percent > self::THRESHOLD_PERCENTAGE_MEDIUM) ? 'red' : 'yellow'),
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Return the usage stats for a single node.
     */
    public function getUsageStatsRaw(Node $node): array
    {
        $stats = $node->loadServerSums();

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
     * Return a single node with location and server information.
     */
    public function loadLocationAndServerCount(Node $node, bool $refresh = false): Node
    {
        if (!$node->relationLoaded('location') || $refresh) {
            $node->load('location');
        }

        // This is quite ugly and can probably be improved down the road.
        // And by probably, I mean it should.
        if (is_null($node->servers_count) || $refresh) {
            $node->loadCount('servers');
        }

        return $node;
    }

    /**
     * Attach a paginated set of allocations to a node mode including
     * any servers that are also attached to those allocations.
     */
    public function loadNodeAllocations(Node $node, bool $refresh = false): Node
    {
        $node->setRelation(
            'allocations',
            $node->allocations()
                ->orderByRaw('server_id IS NOT NULL DESC, server_id IS NULL')
                ->orderByRaw('INET_ATON(ip) ASC')
                ->orderBy('port')
                ->with('server:id,name')
                ->paginate(50)
        );

        return $node;
    }

    /**
     * Return a collection of nodes for all locations to use in server creation UI.
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
}
