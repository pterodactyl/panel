<?php

namespace Pterodactyl\Contracts\Repository;

use Generator;
use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\Attributes\SearchableInterface;

interface NodeRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    const THRESHOLD_PERCENTAGE_LOW = 75;
    const THRESHOLD_PERCENTAGE_MEDIUM = 90;

    /**
     * Return the usage stats for a single node.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return array
     */
    public function getUsageStats(Node $node): array;

    /**
     * Return the usage stats for a single node.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return array
     */
    public function getUsageStatsRaw(Node $node): array;

    /**
     * Return all available nodes with a searchable interface.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNodeListingData(): LengthAwarePaginator;

    /**
     * Return a single node with location and server information.
     *
     * @param \Pterodactyl\Models\Node $node
     * @param bool                     $refresh
     * @return \Pterodactyl\Models\Node
     */
    public function loadLocationAndServerCount(Node $node, bool $refresh = false): Node;

    /**
     * Attach a paginated set of allocations to a node mode including
     * any servers that are also attached to those allocations.
     *
     * @param \Pterodactyl\Models\Node $node
     * @param bool                     $refresh
     * @return \Pterodactyl\Models\Node
     */
    public function loadNodeAllocations(Node $node, bool $refresh = false): Node;

    /**
     * Return a collection of nodes for all locations to use in server creation UI.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getNodesForServerCreation(): Collection;

    /**
     * Return the IDs of all nodes that exist in the provided locations and have the space
     * available to support the additional disk and memory provided.
     *
     * @param array $locations
     * @param int   $disk
     * @param int   $memory
     * @return \Generator
     */
    public function getNodesWithResourceUse(array $locations, int $disk, int $memory): Generator;
}
