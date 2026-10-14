<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Models\Node;

/**
 * @template TModel of Node
 *
 * @extends Builder<TModel>
 */
class NodeBuilder extends Builder
{
    /**
     * Public nodes with room for another server of this size, counting their overallocation.
     * Selects the sum_memory and sum_disk that Node::allocatedResources() reads.
     */
    public function viableFor(int $memory, int $disk): static
    {
        $this->select('nodes.*')
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory')
            ->selectRaw('IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->leftJoin('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('nodes.public', 1)
            ->groupBy('nodes.id')
            ->havingRaw('(IFNULL(SUM(servers.memory), 0) + ?) <= (nodes.memory * (1 + (nodes.memory_overallocate / 100)))', [$memory])
            ->havingRaw('(IFNULL(SUM(servers.disk), 0) + ?) <= (nodes.disk * (1 + (nodes.disk_overallocate / 100)))', [$disk]);

        return $this;
    }

    /**
     * Nodes in any of the given locations; an empty list leaves the query unrestricted.
     *
     * @param  list<int>  $locations
     */
    public function inLocations(array $locations): static
    {
        $this->when($locations !== [], fn (Builder $q) => $q->whereIn('nodes.location_id', $locations));

        return $this;
    }

    /**
     * Nodes that accept an egg with these tags: a node without egg tags accepts any egg,
     * any other needs at least one tag in common with it.
     *
     * @param  array<string>  $eggTags
     */
    public function forEggTags(array $eggTags): static
    {
        $this->where(function (Builder $outer) use ($eggTags): void {
            $outer->whereDoesntHave('eggTags');

            if ($eggTags !== []) {
                $outer->orWhereHas('eggTags', fn (Builder $relation) => $relation->whereIn('tags.slug', $eggTags));
            }
        });

        return $this;
    }

    /**
     * Nodes whose deployment tags are exactly the deploy tags: a node reserved for a tag the
     * deploy did not declare is left out, and every declared tag must be on the node. This
     * is matched apart from the egg tags so that their OR never gets a deploy past a
     * reservation.
     *
     * @param  array<string>  $deployTags
     */
    public function forDeployTags(array $deployTags): static
    {
        $this->whereDoesntHave('deploymentTags', fn (Builder $relation) => $relation->whereNotIn('tags.slug', $deployTags));

        foreach ($deployTags as $slug) {
            $this->whereHas('deploymentTags', fn (Builder $relation) => $relation->where('tags.slug', $slug));
        }

        return $this;
    }
}
