<?php

namespace App\Contracts\Repository;

use App\Models\Location;
use Illuminate\Support\Collection;
use App\Contracts\Repository\Attributes\SearchableInterface;

interface LocationRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Return locations with a count of nodes and servers attached to it.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllWithDetails(): Collection;

    /**
     * Return all of the available locations with the nodes as a relationship.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllWithNodes(): Collection;

    /**
     * Return all of the nodes and their respective count of servers for a location.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithNodes(int $id): Location;

    /**
     * Return a location and the count of nodes in that location.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithNodeCount(int $id): Location;
}
