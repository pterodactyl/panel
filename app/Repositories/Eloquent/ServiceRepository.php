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
     * Return a service or all services with their associated options, variables, and packs.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Service
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithOptions(int $id = null)
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
     * Return a service or all services and the count of options, packs, and servers for that service.
     *
     * @param int|null $id
     * @return \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Service
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithCounts(int $id = null)
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
     * Return a service along with its associated options and the servers relation on those options.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Service
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
