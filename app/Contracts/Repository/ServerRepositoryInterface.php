<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServerRepositoryInterface extends RepositoryInterface
{
    /**
     * Load the egg relations onto the server model.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool $refresh
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
     * Return a collection of servers with their associated data for reinstall operations.
     *
     * @param int|null $server
     * @param int|null $node
     * @return \Illuminate\Support\Collection
     */
    public function getDataForReinstall(int $server = null, int $node = null): Collection;

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
     * @param bool $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function getPrimaryAllocation(Server $server, bool $refresh = false): Server;

    /**
     * Return enough data to be used for the creation of a server via the daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function getDataForCreation(Server $server, bool $refresh = false): Server;

    /**
     * Load associated databases onto the server model.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function loadDatabaseRelations(Server $server, bool $refresh = false): Server;

    /**
     * Get data for use when updating a server on the Daemon. Returns an array of
     * the egg which is used for build and rebuild. Only loads relations
     * if they are missing, or refresh is set to true.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool $refresh
     * @return array
     */
    public function getDaemonServiceData(Server $server, bool $refresh = false): array;

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
     * Return all of the servers that should have a power action performed against them.
     *
     * @param int[] $servers
     * @param int[] $nodes
     * @param bool $returnCount
     * @return int|\Illuminate\Support\LazyCollection
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

    /**
     * Get the amount of servers that are suspended.
     *
     * @return int
     */
    public function getSuspendedServersCount(): int;

    /**
     * Returns all of the servers that exist for a given node in a paginated response.
     *
     * @param int $node
     * @param int $limit
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function loadAllServersForNode(int $node, int $limit): LengthAwarePaginator;
}
