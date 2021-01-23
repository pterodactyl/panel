<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Node;
use Illuminate\Support\Collection;

interface NodeRepositoryInterface extends RepositoryInterface
{
    public const THRESHOLD_PERCENTAGE_LOW = 75;
    public const THRESHOLD_PERCENTAGE_MEDIUM = 90;

    /**
     * Return the usage stats for a single node.
     */
    public function getUsageStats(Node $node): array;

    /**
     * Return the usage stats for a single node.
     */
    public function getUsageStatsRaw(Node $node): array;

    /**
     * Return a single node with location and server information.
     */
    public function loadLocationAndServerCount(Node $node, bool $refresh = false): Node;

    /**
     * Attach a paginated set of allocations to a node mode including
     * any servers that are also attached to those allocations.
     */
    public function loadNodeAllocations(Node $node, bool $refresh = false): Node;

    /**
     * Return a collection of nodes for all locations to use in server creation UI.
     */
    public function getNodesForServerCreation(): Collection;
}
