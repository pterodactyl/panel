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
use Illuminate\Support\Collection;

interface ServiceRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a service or all services with their associated options, variables, and packs.
     *
     * @param int $id
     * @return \Illuminate\Support\Collection
     */
    public function getWithOptions(int $id = null): Collection;

    /**
     * Return a service or all services and the count of options, packs, and servers for that service.
     *
     * @param int|null $id
     * @return \Illuminate\Support\Collection
     */
    public function getWithCounts(int $id = null): Collection;

    /**
     * Return a service along with its associated options and the servers relation on those options.
     *
     * @param int $id
     * @return mixed
     */
    public function getWithOptionServers(int $id): Service;
}
