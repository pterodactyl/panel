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
     * {@inheritdoc}
     */
    public function getWithVariables($id)
    {
        return $this->getBuilder()->with('variables')->find($id, $this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getWithCopyFrom($id)
    {
        return $this->getBuilder()->with('copyFrom')->find($id, $this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function isCopiableScript($copyFromId, $service)
    {
        return $this->getBuilder()->whereNull('copy_script_from')
            ->where('id', '=', $copyFromId)
            ->where('service_id', '=', $service)
            ->exists();
    }
}
