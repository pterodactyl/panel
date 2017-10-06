<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\ServiceOption;
use Illuminate\Database\Eloquent\Collection;

interface ServiceOptionRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a service option with the variables relation attached.
     *
     * @param int $id
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithVariables(int $id): ServiceOption;

    /**
     * Return all of the service options and their relations to be used in the daemon API.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllWithCopyAttributes(): Collection;

    /**
     * Return a service option with the scriptFrom and configFrom relations loaded onto the model.
     *
     * @param int|string $value
     * @param string     $column
     * @return \Pterodactyl\Models\ServiceOption
     */
    public function getWithCopyAttributes($value, string $column = 'id'): ServiceOption;

    /**
     * Return all of the data needed to export a service.
     *
     * @param int $id
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithExportAttributes(int $id): ServiceOption;

    /**
     * Confirm a copy script belongs to the same service as the item trying to use it.
     *
     * @param int $copyFromId
     * @param int $service
     * @return bool
     */
    public function isCopiableScript(int $copyFromId, int $service): bool;
}
