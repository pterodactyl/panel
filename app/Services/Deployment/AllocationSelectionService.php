<?php

namespace Pterodactyl\Services\Deployment;

use Pterodactyl\Models\Allocation;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;

class AllocationSelectionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * @var bool
     */
    protected $dedicated = false;

    /**
     * @var array
     */
    protected $nodes = [];

    /**
     * @var array
     */
    protected $ports = [];

    /**
     * AllocationSelectionService constructor.
     */
    public function __construct(AllocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Toggle if the selected allocation should be the only allocation belonging
     * to the given IP address. If true an allocation will not be selected if an IP
     * already has another server set to use on if its allocations.
     *
     * @return $this
     */
    public function setDedicated(bool $dedicated)
    {
        $this->dedicated = $dedicated;

        return $this;
    }

    /**
     * A list of node IDs that should be used when selecting an allocation. If empty, all
     * nodes will be used to filter with.
     *
     * @return $this
     */
    public function setNodes(array $nodes)
    {
        $this->nodes = $nodes;

        return $this;
    }

    /**
     * An array of individual ports or port ranges to use when selecting an allocation. If
     * empty, all ports will be considered when finding an allocation. If set, only ports appearing
     * in the array or range will be used.
     *
     * @return $this
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function setPorts(array $ports)
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
        $allocation = $this->repository->getRandomAllocation($this->nodes, $this->ports, $this->dedicated);

        if (is_null($allocation)) {
            throw new NoViableAllocationException(trans('exceptions.deployment.no_viable_allocations'));
        }

        return $allocation;
    }
}
