<?php

namespace Pterodactyl\Repositories\Eloquent;

use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class AllocationRepository extends EloquentRepository implements AllocationRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Allocation::class;
    }

    /**
     * Set an array of allocation IDs to be assigned to a specific server.
     *
     * @param int|null $server
     * @param array    $ids
     * @return int
     */
    public function assignAllocationsToServer(int $server = null, array $ids): int
    {
        return $this->getBuilder()->whereIn('id', $ids)->update(['server_id' => $server]);
    }

    /**
     * Return all of the allocations for a specific node.
     *
     * @param int $node
     * @return \Illuminate\Support\Collection
     */
    public function getAllocationsForNode(int $node): Collection
    {
        return $this->getBuilder()->where('node_id', $node)->get($this->getColumns());
    }

    /**
     * Return all of the allocations for a node in a paginated format.
     *
     * @param int $node
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedAllocationsForNode(int $node, int $perPage = 100): LengthAwarePaginator
    {
        return $this->getBuilder()->where('node_id', $node)->paginate($perPage, $this->getColumns());
    }

    /**
     * Return all of the unique IPs that exist for a given node.
     *
     * @param int $node
     * @return \Illuminate\Support\Collection
     */
    public function getUniqueAllocationIpsForNode(int $node): Collection
    {
        return $this->getBuilder()->where('node_id', $node)
            ->groupBy('ip')
            ->orderByRaw('INET_ATON(ip) ASC')
            ->get($this->getColumns());
    }

    /**
     * Return all of the allocations that exist for a node that are not currently
     * allocated.
     *
     * @param int $node
     * @return array
     */
    public function getUnassignedAllocationIds(int $node): array
    {
        $results = $this->getBuilder()->select('id')->whereNull('server_id')->where('node_id', $node)->get();

        return $results->pluck('id')->toArray();
    }

    /**
     * Get an array of all allocations that are currently assigned to a given server.
     *
     * @param int $server
     * @return array
     */
    public function getAssignedAllocationIds(int $server): array
    {
        $results = $this->getBuilder()->select('id')->where('server_id', $server)->get();

        return $results->pluck('id')->toArray();
    }

    /**
     * Return a concated result set of node ips that already have at least one
     * server assigned to that IP. This allows for filtering out sets for
     * dedicated allocation IPs.
     *
     * If an array of nodes is passed the results will be limited to allocations
     * in those nodes.
     *
     * @param array $nodes
     * @return array
     */
    public function getDiscardableDedicatedAllocations(array $nodes = []): array
    {
        $instance = $this->getBuilder()->select(
            $this->getBuilder()->raw('CONCAT_WS("-", node_id, ip) as result')
        );

        if (! empty($nodes)) {
            $instance->whereIn('node_id', $nodes);
        }

        $results = $instance->whereNotNull('server_id')
            ->groupBy($this->getBuilder()->raw('CONCAT(node_id, ip)'))
            ->get();

        return $results->pluck('result')->toArray();
    }

    /**
     * Return a single allocation from those meeting the requirements.
     *
     * @param array $nodes
     * @param array $ports
     * @param bool  $dedicated
     * @return \Pterodactyl\Models\Allocation|null
     */
    public function getRandomAllocation(array $nodes, array $ports, bool $dedicated = false)
    {
        $instance = $this->getBuilder()->whereNull('server_id');

        if (! empty($nodes)) {
            $instance->whereIn('node_id', $nodes);
        }

        if (! empty($ports)) {
            $instance->where(function (Builder $query) use ($ports) {
                $whereIn = [];
                foreach ($ports as $port) {
                    if (is_array($port)) {
                        $query->orWhereBetween('port', $port);
                        continue;
                    }

                    $whereIn[] = $port;
                }

                if (! empty($whereIn)) {
                    $query->orWhereIn('port', $whereIn);
                }
            });
        }

        // If this allocation should not be shared with any other servers get
        // the data and modify the query as necessary,
        if ($dedicated) {
            $discard = $this->getDiscardableDedicatedAllocations($nodes);

            if (! empty($discard)) {
                $instance->whereNotIn(
                    $this->getBuilder()->raw('CONCAT_WS("-", node_id, ip)'), $discard
                );
            }
        }

        return $instance->inRandomOrder()->first();
    }
}
