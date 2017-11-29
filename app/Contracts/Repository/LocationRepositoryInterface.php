<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Contracts\Repository\Attributes\SearchableInterface;

interface LocationRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Return locations with a count of nodes and servers attached to it.
     *
     * @return mixed
     */
    public function getAllWithDetails();

    /**
     * Return all of the available locations with the nodes as a relationship.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllWithNodes();

    /**
     * Return all of the nodes and their respective count of servers for a location.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithNodes($id);

    /**
     * Return a location and the count of nodes in that location.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithNodeCount($id);
}
