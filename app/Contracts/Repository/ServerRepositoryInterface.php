<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServerRepositoryInterface extends RepositoryInterface
{
    /**
     * Load the egg relations onto the server model.
     */
    public function loadEggRelations(Server $server, bool $refresh = false): Server;

    /**
     * Return a collection of servers with their associated data for rebuild operations.
     */
    public function getDataForRebuild(int $server = null, int $node = null): Collection;

    /**
     * Return a collection of servers with their associated data for reinstall operations.
     */
    public function getDataForReinstall(int $server = null, int $node = null): Collection;

    /**
     * Return a server model and all variables associated with the server.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function findWithVariables(int $id): Server;

    /**
     * Get the primary allocation for a given server. If a model is passed into
     * the function, load the allocation relationship onto it. Otherwise, find and
     * return the server from the database.
     */
    public function getPrimaryAllocation(Server $server, bool $refresh = false): Server;

    /**
     * Return enough data to be used for the creation of a server via the daemon.
     */
    public function getDataForCreation(Server $server, bool $refresh = false): Server;

    /**
     * Load associated databases onto the server model.
     */
    public function loadDatabaseRelations(Server $server, bool $refresh = false): Server;

    /**
     * Get data for use when updating a server on the Daemon. Returns an array of
     * the egg which is used for build and rebuild. Only loads relations
     * if they are missing, or refresh is set to true.
     */
    public function getDaemonServiceData(Server $server, bool $refresh = false): array;

    /**
     * Return a server by UUID.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getByUuid(string $uuid): Server;

    /**
     * Check if a given UUID and UUID-Short string are unique to a server.
     */
    public function isUniqueUuidCombo(string $uuid, string $short): bool;

    /**
     * Returns all the servers that exist for a given node in a paginated response.
     */
    public function loadAllServersForNode(int $node, int $limit): LengthAwarePaginator;
}
