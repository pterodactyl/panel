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

interface NodeRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Return the usage stats for a single node.
     *
     * @param int $id
     * @return array
     */
    public function getUsageStats($id);

    /**
     * Return all available nodes with a searchable interface.
     *
     * @param int $count
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNodeListingData($count = 25);

    /**
     * Return a single node with location and server information.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getSingleNode($id);

    /**
     * Return a node with all of the associated allocations and servers that are attached to said allocations.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getNodeAllocations($id);

    /**
     * Return a node with all of the servers attached to that node.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getNodeServers($id);

    /**
     * Return a collection of nodes for all locations to use in server creation UI.
     *
     * @return mixed
     */
    public function getNodesForServerCreation();
}
