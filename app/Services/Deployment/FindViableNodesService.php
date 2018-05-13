<?php

namespace Pterodactyl\Services\Deployment;

use Webmozart\Assert\Assert;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;

class FindViableNodesService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * @var array
     */
    protected $locations = [];

    /**
     * @var int
     */
    protected $disk;

    /**
     * @var int
     */
    protected $memory;

    /**
     * FindViableNodesService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(NodeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Set the locations that should be searched through to locate available nodes.
     *
     * @param array $locations
     * @return $this
     */
    public function setLocations(array $locations): self
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * Set the amount of disk that will be used by the server being created. Nodes will be
     * filtered out if they do not have enough available free disk space for this server
     * to be placed on.
     *
     * @param int $disk
     * @return $this
     */
    public function setDisk(int $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set the amount of memory that this server will be using. As with disk space, nodes that
     * do not have enough free memory will be filtered out.
     *
     * @param int $memory
     * @return $this
     */
    public function setMemory(int $memory): self
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Returns an array of nodes that meet the provided requirements and can then
     * be passed to the AllocationSelectionService to return a single allocation.
     *
     * This functionality is used for automatic deployments of servers and will
     * attempt to find all nodes in the defined locations that meet the disk and
     * memory availability requirements. Any nodes not meeting those requirements
     * are tossed out, as are any nodes marked as non-public, meaning automatic
     * deployments should not be done against them.
     *
     * @return int[]
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     */
    public function handle(): array
    {
        Assert::integer($this->disk, 'Calls to ' . __METHOD__ . ' must have the disk space set as an integer, received %s');
        Assert::integer($this->memory, 'Calls to ' . __METHOD__ . ' must have the memory usage set as an integer, received %s');

        $nodes = $this->repository->getNodesWithResourceUse($this->locations, $this->disk, $this->memory);
        $viable = [];

        foreach ($nodes as $node) {
            $memoryLimit = $node->memory * (1 + ($node->memory_overallocate / 100));
            $diskLimit = $node->disk * (1 + ($node->disk_overallocate / 100));

            if (($node->sum_memory + $this->memory) > $memoryLimit || ($node->sum_disk + $this->disk) > $diskLimit) {
                continue;
            }

            $viable[] = $node->id;
        }

        if (empty($viable)) {
            throw new NoViableNodeException(trans('exceptions.deployment.no_viable_nodes'));
        }

        return $viable;
    }
}
