<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\User;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Pterodactyl\Repositories\Concerns\Searchable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class ServerRepository extends EloquentRepository implements ServerRepositoryInterface
{
    use Searchable;

    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Server::class;
    }

    /**
     * Returns a listing of all servers that exist including relationships.
     *
     * @param int $paginate
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllServers(int $paginate): LengthAwarePaginator
    {
        $instance = $this->getBuilder()->with('node', 'user', 'allocation')->search($this->getSearchTerm());

        return $instance->paginate($paginate, $this->getColumns());
    }

    /**
     * Load the egg relations onto the server model.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function loadEggRelations(Server $server, bool $refresh = false): Server
    {
        if (! $server->relationLoaded('egg') || $refresh) {
            $server->load('egg.scriptFrom');
        }

        return $server;
    }

    /**
     * Return a collection of servers with their associated data for rebuild operations.
     *
     * @param int|null $server
     * @param int|null $node
     * @return \Illuminate\Support\Collection
     */
    public function getDataForRebuild(int $server = null, int $node = null): Collection
    {
        $instance = $this->getBuilder()->with(['allocation', 'allocations', 'pack', 'egg', 'node']);

        if (! is_null($server) && is_null($node)) {
            $instance = $instance->where('id', '=', $server);
        } elseif (is_null($server) && ! is_null($node)) {
            $instance = $instance->where('node_id', '=', $node);
        }

        return $instance->get($this->getColumns());
    }

    /**
     * Return a server model and all variables associated with the server.
     *
     * @param int $id
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function findWithVariables(int $id): Server
    {
        try {
            return $this->getBuilder()->with('egg.variables', 'variables')
                ->where($this->getModel()->getKeyName(), '=', $id)
                ->firstOrFail($this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException;
        }
    }

    /**
     * Get the primary allocation for a given server. If a model is passed into
     * the function, load the allocation relationship onto it. Otherwise, find and
     * return the server from the database.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function getPrimaryAllocation(Server $server, bool $refresh = false): Server
    {
        if (! $server->relationLoaded('allocation') || $refresh) {
            $server->load('allocation');
        }

        return $server;
    }

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
    public function getVariablesWithValues(int $id, bool $returnAsObject = false)
    {
        try {
            $instance = $this->getBuilder()->with('variables', 'egg.variables')->find($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException;
        }

        $data = [];
        $instance->getRelation('egg')->getRelation('variables')->each(function ($item) use (&$data, $instance) {
            $display = $instance->getRelation('variables')->where('variable_id', $item->id)->pluck('variable_value')->first();

            $data[$item->env_variable] = $display ?? $item->default_value;
        });

        if ($returnAsObject) {
            return (object) [
                'data' => $data,
                'server' => $instance,
            ];
        }

        return $data;
    }

    /**
     * Return enough data to be used for the creation of a server via the daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function getDataForCreation(Server $server, bool $refresh = false): Server
    {
        foreach (['allocation', 'allocations', 'pack', 'egg'] as $relation) {
            if (! $server->relationLoaded($relation) || $refresh) {
                $server->load($relation);
            }
        }

        return $server;
    }

    /**
     * Load associated databases onto the server model.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return \Pterodactyl\Models\Server
     */
    public function loadDatabaseRelations(Server $server, bool $refresh = false): Server
    {
        if (! $server->relationLoaded('databases') || $refresh) {
            $server->load('databases.host');
        }

        return $server;
    }

    /**
     * Get data for use when updating a server on the Daemon. Returns an array of
     * the egg and pack UUID which are used for build and rebuild. Only loads relations
     * if they are missing, or refresh is set to true.
     *
     * @param \Pterodactyl\Models\Server $server
     * @param bool                       $refresh
     * @return array
     */
    public function getDaemonServiceData(Server $server, bool $refresh = false): array
    {
        if (! $server->relationLoaded('egg') || $refresh) {
            $server->load('egg');
        }

        if (! $server->relationLoaded('pack') || $refresh) {
            $server->load('pack');
        }

        return [
            'egg' => $server->getRelation('egg')->uuid,
            'pack' => is_null($server->getRelation('pack')) ? null : $server->getRelation('pack')->uuid,
        ];
    }

    /**
     * Return a paginated list of servers that a user can access at a given level.
     *
     * @param \Pterodactyl\Models\User $user
     * @param int                      $level
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function filterUserAccessServers(User $user, int $level): LengthAwarePaginator
    {
        $instance = $this->getBuilder()->with(['user']);

        // If access level is set to owner, only display servers
        // that the user owns.
        if ($level === User::FILTER_LEVEL_OWNER) {
            $instance->where('owner_id', $user->id);
        }

        // If set to all, display all servers they can access, including
        // those they access as an admin. If set to subuser, only return the servers they can access because
        // they are owner, or marked as a subuser of the server.
        elseif (($level === User::FILTER_LEVEL_ALL && ! $user->root_admin) || $level === User::FILTER_LEVEL_SUBUSER) {
            $instance->whereIn('id', $this->getUserAccessServers($user->id));
        }

        // If set to admin, only display the servers a user can access
        // as an administrator (leaves out owned and subuser of).
        elseif ($level === User::FILTER_LEVEL_ADMIN && $user->root_admin) {
            $instance->whereNotIn('id', $this->getUserAccessServers($user->id));
        }

        return $instance->search($this->getSearchTerm())->paginate(25);
    }

    /**
     * Return a server by UUID.
     *
     * @param string $uuid
     * @return \Pterodactyl\Models\Server
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getByUuid(string $uuid): Server
    {
        Assert::notEmpty($uuid, 'Expected non-empty string as first argument passed to ' . __METHOD__);

        try {
            return $this->getBuilder()->with('nest', 'node')->where(function ($query) use ($uuid) {
                $query->where('uuidShort', $uuid)->orWhere('uuid', $uuid);
            })->firstOrFail($this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException;
        }
    }

    /**
     * Return an array of server IDs that a given user can access based
     * on owner and subuser permissions.
     *
     * @param int $user
     * @return int[]
     */
    private function getUserAccessServers(int $user): array
    {
        return $this->getBuilder()->select('id')->where('owner_id', $user)->union(
            $this->app->make(SubuserRepository::class)->getBuilder()->select('server_id')->where('user_id', $user)
        )->pluck('id')->all();
    }
}
