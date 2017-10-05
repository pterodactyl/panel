<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

interface ServiceOptionRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a service option with the variables relation attached.
     *
     * @param int $id
     * @return mixed
     */
    public function getWithVariables($id);

    /**
     * Return a service option with the copyFrom relation loaded onto the model.
     *
     * @param int $id
     * @return mixed
     */
    public function getWithCopyFrom($id);

    /**
     * Confirm a copy script belongs to the same service as the item trying to use it.
     *
     * @param int $copyFromId
     * @param int $service
     * @return bool
     */
    public function isCopiableScript($copyFromId, $service);
}
