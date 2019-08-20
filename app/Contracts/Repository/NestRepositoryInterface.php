<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Contracts\Repository;

use App\Models\Nest;

interface NestRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a nest or all nests with their associated eggs, variables, and packs.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection|\App\Models\Nest
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggs(int $id = null);

    /**
     * Return a nest or all nests and the count of eggs, packs, and servers for that nest.
     *
     * @param int|null $id
     * @return \App\Models\Nest|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithCounts(int $id = null);

    /**
     * Return a nest along with its associated eggs and the servers relation on those eggs.
     *
     * @param int $id
     * @return \App\Models\Nest
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggServers(int $id): Nest;
}
