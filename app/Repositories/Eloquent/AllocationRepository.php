<?php

namespace Pterodactyl\Repositories\Eloquent;

use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
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
}
