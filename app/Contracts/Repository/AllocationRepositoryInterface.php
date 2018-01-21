<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AllocationRepositoryInterface extends RepositoryInterface
{
    /**
     * Set an array of allocation IDs to be assigned to a specific server.
     *
     * @param int|null $server
     * @param array    $ids
     * @return int
     */
    public function assignAllocationsToServer(int $server = null, array $ids): int;

    /**
     * Return all of the allocations for a specific node.
     *
     * @param int $node
     * @return \Illuminate\Support\Collection
     */
    public function getAllocationsForNode(int $node): Collection;

    /**
     * Return all of the allocations for a node in a paginated format.
     *
     * @param int $node
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedAllocationsForNode(int $node, int $perPage = 100): LengthAwarePaginator;

    /**
     * Return all of the unique IPs that exist for a given node.
     *
     * @param int $node
     * @return \Illuminate\Support\Collection
     */
    public function getUniqueAllocationIpsForNode(int $node): Collection;

    /**
     * Return all of the allocations that exist for a node that are not currently
     * allocated.
     *
     * @param int $node
     * @return array
     */
    public function getUnassignedAllocationIds(int $node): array;

    /**
     * Get an array of all allocations that are currently assigned to a given server.
     *
     * @param int $server
     * @return array
     */
    public function getAssignedAllocationIds(int $server): array;
}
