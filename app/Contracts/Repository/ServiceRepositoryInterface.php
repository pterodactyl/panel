<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Service;

interface ServiceRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a service or all services with their associated options, variables, and packs.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Service
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithOptions(int $id = null);

    /**
     * Return a service or all services and the count of options, packs, and servers for that service.
     *
     * @param int|null $id
     * @return \Pterodactyl\Models\Service|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithCounts(int $id = null);

    /**
     * Return a service along with its associated options and the servers relation on those options.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Service
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithOptionServers(int $id): Service;
}
