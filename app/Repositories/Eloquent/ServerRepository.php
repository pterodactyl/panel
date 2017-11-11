<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Concerns\Searchable;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class ServerRepository extends EloquentRepository implements ServerRepositoryInterface
{
    use Searchable;

    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Server::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllServers($paginate = 25)
    {
        Assert::nullOrIntegerish($paginate, 'First argument passed to getAllServers must be integer or null, received %s.');

        $instance = $this->getBuilder()->with('node', 'user', 'allocation')->search($this->searchTerm);

        return is_null($paginate) ? $instance->get($this->getColumns()) : $instance->paginate($paginate, $this->getColumns());
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
     * {@inheritdoc}
     */
    public function getDataForRebuild($server = null, $node = null)
    {
        Assert::nullOrIntegerish($server, 'First argument passed to getDataForRebuild must be null or integer, received %s.');
        Assert::nullOrIntegerish($node, 'Second argument passed to getDataForRebuild must be null or integer, received %s.');

        $instance = $this->getBuilder()->with('allocation', 'allocations', 'pack', 'egg', 'node');

        if (! is_null($server) && is_null($node)) {
            $instance = $instance->where('id', '=', $server);
        } elseif (is_null($server) && ! is_null($node)) {
            $instance = $instance->where('node_id', '=', $node);
        }

        return $instance->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findWithVariables($id)
    {
        Assert::integerish($id, 'First argument passed to findWithVariables must be integer, received %s.');

        $instance = $this->getBuilder()->with('egg.variables', 'variables')
            ->where($this->getModel()->getKeyName(), '=', $id)
            ->first($this->getColumns());

        if (is_null($instance)) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }

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
    public function getPrimaryAllocation($server, bool $refresh = false): Server
    {
        $instance = $server;
        if (! $instance instanceof Server) {
            Assert::integerish($server, 'First argument passed to getPrimaryAllocation must be instance of \Pterodactyl\Models\Server or integer, received %s.');
            $instance = $this->getBuilder()->find($server, $this->getColumns());
        }

        if (! $instance) {
            throw new RecordNotFoundException;
        }

        if (! $instance->relationLoaded('allocation') || $refresh) {
            $instance->load('allocation');
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariablesWithValues($id, $returnWithObject = false)
    {
        $instance = $this->getBuilder()->with('variables', 'egg.variables')
                         ->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        $data = [];
        $instance->egg->variables->each(function ($item) use (&$data, $instance) {
            $display = $instance->variables->where('variable_id', $item->id)->pluck('variable_value')->first();

            $data[$item->env_variable] = $display ?? $item->default_value;
        });

        if ($returnWithObject) {
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
     * {@inheritdoc}
     */
    public function getWithDatabases($id)
    {
        $instance = $this->getBuilder()->with('databases.host')
                         ->where('installed', 1)
                         ->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return $instance;
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
     * {@inheritdoc}
     */
    public function getUserAccessServers($user)
    {
        Assert::numeric($user, 'First argument passed to getUserAccessServers must be numeric, received %s.');

        $subuser = $this->app->make(SubuserRepository::class);

        return $this->getBuilder()->select('id')->where('owner_id', $user)->union(
            $subuser->getBuilder()->select('server_id')->where('user_id', $user)
        )->pluck('id')->all();
    }

    /**
     * {@inheritdoc}
     */
    public function filterUserAccessServers($user, $admin = false, $level = 'all', array $relations = [])
    {
        Assert::numeric($user, 'First argument passed to filterUserAccessServers must be numeric, received %s.');
        Assert::boolean($admin, 'Second argument passed to filterUserAccessServers must be boolean, received %s.');
        Assert::stringNotEmpty($level, 'Third argument passed to filterUserAccessServers must be a non-empty string, received %s.');

        $instance = $this->getBuilder()->with($relations);

        // If access level is set to owner, only display servers
        // that the user owns.
        if ($level === 'owner') {
            $instance->where('owner_id', $user);
        }

        // If set to all, display all servers they can access, including
        // those they access as an admin.
        //
        // If set to subuser, only return the servers they can access because
        // they are owner, or marked as a subuser of the server.
        if (($level === 'all' && ! $admin) || $level === 'subuser') {
            $instance->whereIn('id', $this->getUserAccessServers($user));
        }

        // If set to admin, only display the servers a user can access
        // as an administrator (leaves out owned and subuser of).
        if ($level === 'admin' && $admin) {
            $instance->whereIn('id', $this->getUserAccessServers($user));
        }

        return $instance->search($this->searchTerm)->paginate(
            $this->app->make('config')->get('pterodactyl.paginate.frontend.servers')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getByUuid($uuid)
    {
        Assert::stringNotEmpty($uuid, 'First argument passed to getByUuid must be a non-empty string, received %s.');

        $instance = $this->getBuilder()->with('nest', 'node')->where(function ($query) use ($uuid) {
            $query->where('uuidShort', $uuid)->orWhere('uuid', $uuid);
        })->first($this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }
}
