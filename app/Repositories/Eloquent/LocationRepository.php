<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Location;
use Illuminate\Support\Collection;
use Pterodactyl\Repositories\Concerns\Searchable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationRepository extends EloquentRepository implements LocationRepositoryInterface
{
    use Searchable;

    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Location::class;
    }

    /**
     * Return locations with a count of nodes and servers attached to it.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllWithDetails(): Collection
    {
        return $this->getBuilder()->withCount('nodes', 'servers')->get($this->getColumns());
    }

    /**
     * Return all of the available locations with the nodes as a relationship.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllWithNodes(): Collection
    {
        return $this->getBuilder()->with('nodes')->get($this->getColumns());
    }

    /**
     * Return all of the nodes and their respective count of servers for a location.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithNodes(int $id): Location
    {
        try {
            return $this->getBuilder()->with('nodes.servers')->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException;
        }
    }

    /**
     * Return a location and the count of nodes in that location.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithNodeCount(int $id): Location
    {
        try {
            return $this->getBuilder()->withCount('nodes')->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException;
        }
    }
}
