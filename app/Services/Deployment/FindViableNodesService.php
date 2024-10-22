<?php

namespace Pterodactyl\Services\Deployment;

use Pterodactyl\Models\Node;
use Webmozart\Assert\Assert;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;

class FindViableNodesService
{
    protected array $locations = [];
    protected ?int $disk = null;
    protected ?int $memory = null;

    /**
     * Set the locations that should be searched through to locate available nodes.
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
     */
    public function setDisk(int $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set the amount of memory that this server will be using. As with disk space, nodes that
     * do not have enough free memory will be filtered out.
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
     * @param int|null $page If provided the results will be paginated by returning
     *                       up to 50 nodes at a time starting at the provided page.
     *                       If "null" is provided as the value no pagination will
     *                       be used.
     *
     * @throws NoViableNodeException
     */
    public function handle(?int $perPage = null, ?int $page = null): LengthAwarePaginator|Collection
    {
        Assert::integer($this->disk, 'Disk space must be an int, got %s');
        Assert::integer($this->memory, 'Memory usage must be an int, got %s');

        $query = Node::query()->select('nodes.*')
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory')
            ->selectRaw('IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->leftJoin('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('nodes.public', 1);

        if (!empty($this->locations)) {
            $query = $query->whereIn('nodes.location_id', $this->locations);
        }

        $results = $query->groupBy('nodes.id')
            ->havingRaw('(IFNULL(SUM(servers.memory), 0) + ?) <= (nodes.memory * (1 + (nodes.memory_overallocate / 100)))', [$this->memory])
            ->havingRaw('(IFNULL(SUM(servers.disk), 0) + ?) <= (nodes.disk * (1 + (nodes.disk_overallocate / 100)))', [$this->disk]);

        if (!is_null($page)) {
            $results = $results->paginate($perPage ?? 50, ['*'], 'page', $page);
        } else {
            $results = $results->get()->toBase();
        }

        if ($results->isEmpty()) {
            throw new NoViableNodeException(trans('exceptions.deployment.no_viable_nodes'));
        }

        return $results;
    }
}
