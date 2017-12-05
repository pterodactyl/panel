<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Server;
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
     * Load the egg relations onto the server model.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function loadEggRelations(Server $server, bool $refresh = false): Server;

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
     * Get the primary allocation for a given server. If a model is passed into
     * the function, load the allocation relationship onto it. Otherwise, find and
     * return the server from the database.
     *
     * @param int|\Pterodactyl\Models\Server $server
     * @param bool                           $refresh
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getPrimaryAllocation($server, bool $refresh = false): Server;

    /**
     * Return enough data to be used for the creation of a server via the daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function getDataForCreation(Server $server, bool $refresh = false): Server;

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
     * Get data for use when updating a server on the Daemon. Returns an array of
     * the egg and pack UUID which are used for build and rebuild. Only loads relations
     * if they are missing, or refresh is set to true.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return array
     */
    public function getDaemonServiceData(Server $server, bool $refresh = false): array;

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
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getByUuid($uuid);
}
