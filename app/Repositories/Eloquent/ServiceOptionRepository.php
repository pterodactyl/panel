<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;

class ServiceOptionRepository extends EloquentRepository implements ServiceOptionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return ServiceOption::class;
    }

    /**
     * Return a service option with the variables relation attached.
     *
     * @param int $id
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithVariables(int $id): ServiceOption
    {
        /** @var \Pterodactyl\Models\ServiceOption $instance */
        $instance = $this->getBuilder()->with('variables')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * Return a service option with the scriptFrom and configFrom relations loaded onto the model.
     *
     * @param int $id
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithCopyAttributes(int $id): ServiceOption
    {
        /** @var \Pterodactyl\Models\ServiceOption $instance */
        $instance = $this->getBuilder()->with('scriptFrom', 'configFrom')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * Return all of the data needed to export a service.
     *
     * @param int $id
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithExportAttributes(int $id): ServiceOption
    {
        /** @var \Pterodactyl\Models\ServiceOption $instance */
        $instance = $this->getBuilder()->with('scriptFrom', 'configFrom', 'variables')->find($id, $this->getColumns());
        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * Confirm a copy script belongs to the same service as the item trying to use it.
     *
     * @param int $copyFromId
     * @param int $service
     * @return bool
     */
    public function isCopiableScript(int $copyFromId, int $service): bool
    {
        return $this->getBuilder()->whereNull('copy_script_from')
            ->where('id', '=', $copyFromId)
            ->where('service_id', '=', $service)
            ->exists();
    }
}
