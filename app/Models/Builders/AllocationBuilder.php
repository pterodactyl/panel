<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Models\Allocation;

/**
 * @template TModel of Allocation
 *
 * @extends Builder<TModel>
 */
class AllocationBuilder extends Builder
{
    public function unassigned(): static
    {
        $this->whereNull('server_id');

        return $this;
    }

    /**
     * Allocations on any of the given nodes; an empty list leaves the query unrestricted.
     *
     * @param  array<int>  $nodes
     */
    public function onNodes(array $nodes): static
    {
        $this->when($nodes !== [], fn (Builder $q) => $q->whereIn('node_id', $nodes));

        return $this;
    }

    /**
     * Allocations whose port is one of the given ports or falls inside one of the
     * given [start, end] ranges; with neither given the query is unrestricted.
     *
     * @param  list<int>  $ports
     * @param  list<array{int, int}>  $ranges
     */
    public function onPorts(array $ports, array $ranges = []): static
    {
        if ($ports === [] && $ranges === []) {
            return $this;
        }

        $this->where(function (Builder $inner) use ($ports, $ranges): void {
            if ($ports !== []) {
                $inner->orWhereIn('port', $ports);
            }

            foreach ($ranges as $range) {
                $inner->orWhereBetween('port', $range);
            }
        });

        return $this;
    }

    /**
     * Allocations on node/IP pairs that no server uses yet.
     *
     * @param  array<int>  $nodes  restrict the occupied-IP lookup to these nodes
     */
    public function onDedicatedIp(array $nodes = []): static
    {
        $occupied = Allocation::query()
            ->selectRaw('CONCAT_WS("-", node_id, ip)')
            ->whereNotNull('server_id')
            ->onNodes($nodes);

        $this->whereNotIn($this->raw('CONCAT_WS("-", node_id, ip)'), $occupied);

        return $this;
    }
}
