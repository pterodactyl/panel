<?php

namespace Pterodactyl\Services\Deployment;

use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;

class AllocationSelectionService
{
    protected bool $dedicated = false;

    protected array $nodes = [];

    protected array $ports = [];

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
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function setPorts(array $ports): self
    {
        $stored = [];
        foreach ($ports as $port) {
            if (is_digit($port)) {
                $stored[] = $port;
            }

            // Ranges are stored in the ports array as an array which can be
            // better processed in the repository.
            if (preg_match(AssignmentService::PORT_RANGE_REGEX, $port, $matches)) {
                if (abs($matches[2] - $matches[1]) > AssignmentService::PORT_RANGE_LIMIT) {
                    throw new DisplayException(trans('exceptions.allocations.too_many_ports'));
                }

                $stored[] = [$matches[1], $matches[2]];
            }
        }

        $this->ports = $stored;

        return $this;
    }

    /**
     * Return a single allocation that should be used as the default allocation for a server.
     *
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     */
    public function handle(): Allocation
    {
        $allocation = $this->getRandomAllocation($this->nodes, $this->ports, $this->dedicated);

        if (is_null($allocation)) {
            throw new NoViableAllocationException(trans('exceptions.deployment.no_viable_allocations'));
        }

        return $allocation;
    }

    /**
     * Return a single allocation from those meeting the requirements.
     */
    private function getRandomAllocation(array $nodes = [], array $ports = [], bool $dedicated = false): ?Allocation
    {
        $query = Allocation::query()->whereNull('server_id');

        if (!empty($nodes)) {
            $query->whereIn('node_id', $nodes);
        }

        if (!empty($ports)) {
            $query->where(function (Builder $inner) use ($ports) {
                $whereIn = [];
                foreach ($ports as $port) {
                    if (is_array($port)) {
                        $inner->orWhereBetween('port', $port);
                        continue;
                    }

                    $whereIn[] = $port;
                }

                if (!empty($whereIn)) {
                    $inner->orWhereIn('port', $whereIn);
                }
            });
        }

        // If this allocation should not be shared with any other servers get
        // the data and modify the query as necessary,
        if ($dedicated) {
            $discard = $this->getDiscardableDedicatedAllocations($nodes);

            if (!empty($discard)) {
                $query->whereNotIn(
                    $this->getBuilder()->raw('CONCAT_WS("-", node_id, ip)'),
                    $discard
                );
            }
        }

        return $query->inRandomOrder()->first();
    }

    /**
     * Return a concatenated result set of node ips that already have at least one
     * server assigned to that IP. This allows for filtering out sets for
     * dedicated allocation IPs.
     *
     * If an array of nodes is passed the results will be limited to allocations
     * in those nodes.
     */
    private function getDiscardableDedicatedAllocations(array $nodes = []): array
    {
        $query = Allocation::query()->selectRaw('CONCAT_WS("-", node_id, ip) as result');

        if (!empty($nodes)) {
            $query->whereIn('node_id', $nodes);
        }

        return $query->whereNotNull('server_id')
            ->groupByRaw('CONCAT(node_id, ip)')
            ->get()
            ->pluck('result')
            ->toArray();
    }
}
