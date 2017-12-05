<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Location;
use Pterodactyl\Repositories\Concerns\Searchable;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationRepository extends EloquentRepository implements LocationRepositoryInterface
{
    use Searchable;

    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Location::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithDetails()
    {
        return $this->getBuilder()->withCount('nodes', 'servers')->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithNodes()
    {
        return $this->getBuilder()->with('nodes')->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getWithNodes($id)
    {
        $instance = $this->getBuilder()->with('nodes.servers')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithNodeCount($id)
    {
        $instance = $this->getBuilder()->withCount('nodes')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }
}
