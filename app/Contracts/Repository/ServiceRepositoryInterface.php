<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

interface ServiceRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a service or all services with their associated options, variables, and packs.
     *
     * @param int $id
     * @return \Illuminate\Support\Collection
     */
    public function getWithOptions($id = null);

    /**
     * Return a service along with its associated options and the servers relation on those options.
     *
     * @param int $id
     * @return mixed
     */
    public function getWithOptionServers($id);
}
