<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Nest;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class NestRepository extends EloquentRepository implements NestRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Nest::class;
    }

    /**
     * Return a nest or all nests with their associated eggs and variables.
     *
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Nest
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggs(int $id = null)
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
     * @return \Pterodactyl\Models\Nest|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithCounts(int $id = null)
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
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggServers(int $id): Nest
    {
        $instance = $this->getBuilder()->with('eggs.servers')->find($id, $this->getColumns());
        if (!$instance) {
            throw new RecordNotFoundException();
        }

        /* @var Nest $instance */
        return $instance;
    }
}
