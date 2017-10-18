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

interface ServerRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Returns a listing of all servers that exist including relationships.
     *
     * @param int|null $paginate
     * @return mixed
     */
    public function getAllServers($paginate);

    /**
     * Return a collection of servers with their associated data for rebuild operations.
     *
     * @param int|null $server
     * @param int|null $node
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDataForRebuild($server = null, $node = null);

    /**
     * Return a server model and all variables associated with the server.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function findWithVariables($id);

    /**
     * Return all of the server variables possible and default to the variable
     * default if there is no value defined for the specific server requested.
     *
     * @param int  $id
     * @param bool $returnAsObject
     * @return array|object
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getVariablesWithValues($id, $returnAsObject = false);

    /**
     * Return enough data to be used for the creation of a server via the daemon.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getDataForCreation($id);

    /**
     * Return a server as well as associated databases and their hosts.
     *
     * @param int $id
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithDatabases($id);

    /**
     * Return data about the daemon service in a consumable format.
     *
     * @param int $id
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getDaemonServiceData($id);

    /**
     * Return an array of server IDs that a given user can access based on owner and subuser permissions.
     *
     * @param int $user
     * @return array
     */
    public function getUserAccessServers($user);

    /**
     * Return a paginated list of servers that a user can access at a given level.
     *
     * @param int    $user
     * @param string $level
     * @param bool   $admin
     * @param array  $relations
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function filterUserAccessServers($user, $admin = false, $level = 'all', array $relations = []);

    /**
     * Return a server by UUID.
     *
     * @param string $uuid
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getByUuid($uuid);
}
