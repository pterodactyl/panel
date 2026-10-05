<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Deployment;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Models\Node;

class FindViableNodesService
{
    /**
     * Location IDs as they were handed to setLocations(), which accepts any array
     * and rejects anything that is not an integer or an integer-like string.
     *
     * @var list<int>
     */
    protected array $locations = [];

    protected ?int $disk = null;

    protected ?int $memory = null;

    /**
     * Null means "no egg is known yet", so the game half of the tag gate is skipped
     * entirely. An empty array is a real constraint: an egg carrying no tags can
     * only land on a node that accepts any game.
     *
     * @var string[]|null
     */
    protected ?array $eggTags = null;

    /** @var string[] */
    protected array $deployTags = [];

    public function __construct(private readonly NodeTagGate $tagGate) {}

    /**
     * Set the locations that should be searched through to locate available nodes.
     *
     * @param  list<int|string>  $locations  Rejected element by element unless every entry
     *                                       is an integer or an integer-like string.
     */
    public function setLocations(array $locations): self
    {
        $parsed = [];
        foreach ($locations as $location) {
            throw_if(is_string($location) && preg_match('/^-?\d+$/', $location) !== 1, InvalidArgumentException::class, 'An array of location IDs should be provided when calling setLocations.');

            // SAFETY: integers pass directly and strings are accepted only after the anchored integer check above.
            $parsed[] = (int) $location;
        }

        $this->locations = $parsed;

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
     * Set the tags of the egg being deployed. A node that declares which games it
     * accepts is filtered out unless it shares at least one of these.
     *
     * @param  array<string>  $eggTags
     */
    public function setEggTags(array $eggTags): self
    {
        $this->eggTags = $eggTags;

        return $this;
    }

    /**
     * Set the deploy tags this deployment declares. Nodes reserved for tags the
     * deployment did not declare are filtered out, and once a tag is declared only
     * nodes carrying it remain.
     *
     * @param  array<string>  $deployTags
     */
    public function setDeployTags(array $deployTags): self
    {
        $this->deployTags = $deployTags;

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
     * @param  int|null  $page  If provided the results will be paginated by returning
     *                          up to 50 nodes at a time starting at the provided page.
     *                          If "null" is provided as the value no pagination will
     *                          be used.
     * @return ($page is null ? Collection<int, Node> : LengthAwarePaginator<int, Node>)
     *
     * @throws NoViableNodeException
     */
    public function handle(?int $perPage = null, ?int $page = null): LengthAwarePaginator|Collection
    {
        if (! is_int($this->disk)) {
            throw new InvalidArgumentException(sprintf('Disk space must be an int, got %s', get_debug_type($this->disk)));
        }

        if (! is_int($this->memory)) {
            throw new InvalidArgumentException(sprintf('Memory usage must be an int, got %s', get_debug_type($this->memory)));
        }

        $query = Node::query()->select('nodes.*')
            ->selectRaw('IFNULL(SUM(servers.memory), 0) as sum_memory')
            ->selectRaw('IFNULL(SUM(servers.disk), 0) as sum_disk')
            ->leftJoin('servers', 'servers.node_id', '=', 'nodes.id')
            ->where('nodes.public', 1);

        if ($this->locations !== []) {
            $query = $query->whereIn('nodes.location_id', $this->locations);
        }

        // A preview that has no egg yet can still reproduce the reservation match
        // exactly, so it applies that half on its own rather than nothing at all.
        if (($this->eggTags) === null) {
            $this->tagGate->applyDeploymentTags($query, $this->deployTags);
        } else {
            $this->tagGate->apply($query, $this->eggTags, $this->deployTags);
        }

        $results = $query->groupBy('nodes.id')
            ->havingRaw('(IFNULL(SUM(servers.memory), 0) + ?) <= (nodes.memory * (1 + (nodes.memory_overallocate / 100)))', [$this->memory])
            ->havingRaw('(IFNULL(SUM(servers.disk), 0) + ?) <= (nodes.disk * (1 + (nodes.disk_overallocate / 100)))', [$this->disk]);

        if (($page) !== null) {
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
