<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\Attributes\SearchableInterface;

interface ServerRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Returns a listing of all servers that exist including relationships.
     *
     * @param int $paginate
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllServers(int $paginate): LengthAwarePaginator;

    /**
     * Load the egg relations onto the server model.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function loadEggRelations(Server $server, bool $refresh = false): Server;

    /**
     * Return a collection of servers with their associated data for rebuild operations.
     *
     * @param int|null $server
     * @param int|null $node
     * @return \Illuminate\Support\Collection
     */
    public function getDataForRebuild(int $server = null, int $node = null): Collection;

    /**
     * Return a server model and all variables associated with the server.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function findWithVariables(int $id): Server;

    /**
     * Get the primary allocation for a given server. If a model is passed into
     * the function, load the allocation relationship onto it. Otherwise, find and
     * return the server from the database.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function getPrimaryAllocation(Server $server, bool $refresh = false): Server;

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
    public function getVariablesWithValues(int $id, bool $returnAsObject = false);

    /**
     * Return enough data to be used for the creation of a server via the daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function getDataForCreation(Server $server, bool $refresh = false): Server;

    /**
     * Load associated databases onto the server model.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function loadDatabaseRelations(Server $server, bool $refresh = false): Server;

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
     * Return a paginated list of servers that a user can access at a given level.
     *
     * @param \Pterodactyl\Models\User $user
     * @param int                      $level
     * @param bool                     $paginate
     * @return \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function filterUserAccessServers(User $user, int $level, bool $paginate = true);

    /**
     * Return a server by UUID.
     *
     * @param string $uuid
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getByUuid(string $uuid): Server;

    /**
     * Return all of the servers that should have a power action performed aganist them.
     *
     * @param int[] $servers
     * @param int[] $nodes
     * @param bool  $returnCount
     * @return int|\Generator
     */
    public function getServersForPowerAction(array $servers = [], array $nodes = [], bool $returnCount = false);

    /**
     * Return the total number of servers that will be affected by the query.
     *
     * @param int[] $servers
     * @param int[] $nodes
     * @return int
     */
    public function getServersForPowerActionCount(array $servers = [], array $nodes = []): int;

    /**
     * Check if a given UUID and UUID-Short string are unique to a server.
     *
     * @param string $uuid
     * @param string $short
     * @return bool
     */
    public function isUniqueUuidCombo(string $uuid, string $short): bool;
}
