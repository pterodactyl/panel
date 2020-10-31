<?php

namespace Pterodactyl\Services\Deployment;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Node;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;

class FindViableNodesService
{
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
     * Set the locations that should be searched through to locate available nodes.
     *
     * @param array $locations
     * @return $this
     */
    public function setLocations(array $locations): self
    {
        Assert::allIntegerish($locations, 'An array of location IDs should be provided when calling setLocations.');

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
     * @return \Pterodactyl\Models\Node[]|\Illuminate\Support\Collection
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     */
    public function handle()
    {
        Assert::integer($this->disk, 'Disk space must be an int, got %s');
        Assert::integer($this->memory, 'Memory usage must be an int, got %s');

        $query = Node::query()->select('nodes.*')
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory')
            ->selectRaw('IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->leftJoin('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('nodes.public', 1);

        if (! empty($this->locations)) {
            $query = $query->whereIn('nodes.location_id', $this->locations);
        }

        $results = $query->groupBy('nodes.id')
            ->havingRaw('(IFNULL(SUM(servers.memory), 0) + ?) <= (nodes.memory * (1 + (nodes.memory_overallocate / 100)))', [$this->memory])
            ->havingRaw('(IFNULL(SUM(servers.disk), 0) + ?) <= (nodes.disk * (1 + (nodes.disk_overallocate / 100)))', [$this->disk])
            ->get()
            ->toBase();

        if ($results->isEmpty()) {
            throw new NoViableNodeException(trans('exceptions.deployment.no_viable_nodes'));
        }

        return $results;
    }
}
