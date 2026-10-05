<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Deployment;

use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Actions\Allocations\CreateAllocations;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;
use Pterodactyl\Models\Allocation;

class AllocationSelectionService
{
    protected bool $dedicated = false;

    /** @var list<int> */
    protected array $nodes = [];

    /**
     * Single ports as they were provided.
     *
     * @var list<int>
     */
    protected array $ports = [];

    /**
     * Port ranges reduced to [start, end] pairs for the Allocation::onPorts() scope.
     *
     * @var list<array{int, int}>
     */
    protected array $portRanges = [];

    /**
     * Toggle if the selected allocation should be the only allocation belonging
     * to the given IP address. If true an allocation will not be selected if an IP
     * already has another server set to use on if its allocations.
     */
    public function setDedicated(bool $dedicated): self
    {
        $this->dedicated = $dedicated;

        return $this;
    }

    /**
     * A list of node IDs that should be used when selecting an allocation. If empty, all
     * nodes will be used to filter with.
     *
     * @param  list<int>  $nodes
     */
    public function setNodes(array $nodes): self
    {
        $this->nodes = $nodes;

        return $this;
    }

    /**
     * An array of individual ports or port ranges to use when selecting an allocation. If
     * empty, all ports will be considered when finding an allocation. If set, only ports appearing
     * in the array or range will be used.
     *
     * @param  list<ApiScalar>  $ports  Entries that are neither a port nor a port range
     *                                  are discarded rather than rejected.
     *
     * @throws DisplayException
     */
    public function setPorts(array $ports): self
    {
        $stored = [];
        $ranges = [];
        foreach ($ports as $port) {
            // SAFETY: deployment ports are JSON scalars; string conversion is used only to test decimal port syntax.
            if (! is_bool($port) && ctype_digit((string) $port)) {
                // SAFETY: ctype_digit() above proves the scalar is a decimal integer before normalization.
                $stored[] = (int) $port;
            }

            // Ranges are stored as a [start, end] pair for the onPorts() scope.
            // SAFETY: deployment ports are JSON scalars; string conversion is used only to match the anchored range grammar.
            if (preg_match(CreateAllocations::PORT_RANGE_REGEX, (string) $port, $matches)) {
                if (abs($matches[2] - $matches[1]) > CreateAllocations::PORT_RANGE_LIMIT) {
                    throw new DisplayException(trans('exceptions.allocations.too_many_ports'));
                }

                // SAFETY: PORT_RANGE_REGEX captures two decimal integer strings.
                $ranges[] = [(int) $matches[1], (int) $matches[2]];
            }
        }

        $this->ports = $stored;
        $this->portRanges = $ranges;

        return $this;
    }

    /**
     * Return a single allocation that should be used as the default allocation for a server.
     *
     * @throws NoViableAllocationException
     */
    public function handle(): Allocation
    {
        $allocation = Allocation::query()
            ->unassigned()
            ->onNodes($this->nodes)
            ->onPorts($this->ports, $this->portRanges)
            ->when($this->dedicated, fn (Builder $query) => $query->onDedicatedIp($this->nodes))
            ->inRandomOrder()
            ->first();

        if ($allocation === null) {
            throw new NoViableAllocationException(trans('exceptions.deployment.no_viable_allocations'));
        }

        return $allocation;
    }
}
