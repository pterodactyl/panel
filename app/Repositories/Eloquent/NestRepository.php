<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Nest;
use Illuminate\Database\Eloquent\Collection;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class NestRepository extends EloquentRepository implements NestRepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return Nest::class;
    }

    /**
     * Return a nest or all nests with their associated eggs and variables.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Pterodactyl\Models\Nest>|\Pterodactyl\Models\Nest
     *
     * @throws RecordNotFoundException
     */
    public function getWithEggs(?int $id = null): Collection|Nest
    {
        $instance = $this->getBuilder()->with('eggs', 'eggs.variables');

        if (!is_null($id)) {
            $instance = $instance->find($id, $this->getColumns());
            if (!$instance) {
                throw new RecordNotFoundException();
            }

            return $instance;
        }

        return $instance->get($this->getColumns());
    }

    /**
     * Return a nest or all nests and the count of eggs and servers for that nest.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Pterodactyl\Models\Nest>|\Pterodactyl\Models\Nest
     *
     * @throws RecordNotFoundException
     */
    public function getWithCounts(?int $id = null): Collection|Nest
    {
        $instance = $this->getBuilder()->withCount(['eggs', 'servers']);

        if (!is_null($id)) {
            $instance = $instance->find($id, $this->getColumns());
            if (!$instance) {
                throw new RecordNotFoundException();
            }

            return $instance;
        }

        return $instance->get($this->getColumns());
    }

    /**
     * Return a nest along with its associated eggs and the servers relation on those eggs.
     *
     * @throws RecordNotFoundException
     */
    public function getWithEggServers(int $id): Nest
    {
        $instance = $this->getBuilder()->with('eggs.servers')->find($id, $this->getColumns());
        if (!$instance) {
            throw new RecordNotFoundException();
        }

        return $instance; // @phpstan-ignore return.type
    }
}
