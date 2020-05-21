<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Mount;
use Illuminate\Support\Collection;
use Pterodactyl\Repositories\Concerns\Searchable;

class MountRepository extends EloquentRepository
{
    use Searchable;

    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Mount::class;
    }

    /**
     * Return mounts with a count of eggs, nodes, and servers attached to it.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllWithDetails(): Collection
    {
        return $this->getBuilder()->withCount('eggs', 'nodes')->get($this->getColumns());
    }
}
