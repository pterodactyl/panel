<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;

interface NodeRepositoryInterface extends RepositoryInterface
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
     * Return a single node with location and server information.
     *
     * @param \Pterodactyl\Models\Node $node
     * @param bool $refresh
     * @return \Pterodactyl\Models\Node
     */
    public function loadLocationAndServerCount(Node $node, bool $refresh = false): Node;

    /**
     * Attach a paginated set of allocations to a node mode including
     * any servers that are also attached to those allocations.
     *
     * @param \Pterodactyl\Models\Node $node
     * @param bool $refresh
     * @return \Pterodactyl\Models\Node
     */
    public function loadNodeAllocations(Node $node, bool $refresh = false): Node;

    /**
     * Return a collection of nodes for all locations to use in server creation UI.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getNodesForServerCreation(): Collection;
}
