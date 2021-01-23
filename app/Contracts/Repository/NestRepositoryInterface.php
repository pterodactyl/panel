<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Nest;

interface NestRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a nest or all nests with their associated eggs and variables.
     *
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Nest
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggs(int $id = null);

    /**
     * Return a nest or all nests and the count of eggs and servers for that nest.
     *
     * @return \Pterodactyl\Models\Nest|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithCounts(int $id = null);

    /**
     * Return a nest along with its associated eggs and the servers relation on those eggs.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggServers(int $id): Nest;
}
