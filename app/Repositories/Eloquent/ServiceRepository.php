<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Service;
use Illuminate\Support\Collection;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

class ServiceRepository extends EloquentRepository implements ServiceRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Service::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithOptions(int $id = null): Collection
    {
        $instance = $this->getBuilder()->with('options.packs', 'options.variables');

        if (! is_null($id)) {
            $instance = $instance->find($id, $this->getColumns());
            if (! $instance) {
                throw new RecordNotFoundException;
            }

            return $instance;
        }

        return $instance->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getWithCounts(int $id = null): Collection
    {
        $instance = $this->getBuilder()->withCount(['options', 'packs', 'servers']);

        if (! is_null($id)) {
            $instance = $instance->find($id, $this->getColumns());
            if (! $instance) {
                throw new RecordNotFoundException;
            }

            return $instance;
        }

        return $instance->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getWithOptionServers(int $id): Service
    {
        $instance = $this->getBuilder()->with('options.servers')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        /* @var Service $instance */
        return $instance;
    }
}
