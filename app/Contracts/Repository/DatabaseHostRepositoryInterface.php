<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

interface DatabaseHostRepositoryInterface extends RepositoryInterface
{
    /**
     * Return database hosts with a count of databases and the node information for which it is attached.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getWithViewDetails();

    /**
     * Return a database host with the databases and associated servers that are attached to said databases.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithServers($id);
}
