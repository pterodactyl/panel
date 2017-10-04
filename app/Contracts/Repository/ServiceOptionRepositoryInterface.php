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
     * Return a service option with the scriptFrom and configFrom relations loaded onto the model.
     *
     * @param int $id
     * @return \Pterodactyl\Models\ServiceOption
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithCopyAttributes(int $id): ServiceOption;

    /**
     * Confirm a copy script belongs to the same service as the item trying to use it.
     *
     * @param int $copyFromId
     * @param int $service
     * @return bool
     */
    public function isCopiableScript(int $copyFromId, int $service): bool;
}
