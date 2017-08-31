<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Concerns\Searchable;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Webmozart\Assert\Assert;

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
        $instance = $this->getBuilder()->with('node', 'user', 'allocation');

        if ($this->searchTerm) {
            $instance->search($this->searchTerm);
        }

        return $instance->paginate($paginate);
    }

    /**
     * {@inheritdoc}
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findWithVariables($id)
    {
        $instance = $this->getBuilder()->with('option.variables', 'variables')
                         ->where($this->getModel()->getKeyName(), '=', $id)
                         ->first($this->getColumns());

        if (is_null($instance)) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariablesWithValues($id, $returnWithObject = false)
    {
        $instance = $this->getBuilder()->with('variables', 'option.variables')
                         ->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        $data = [];
        $instance->option->variables->each(function ($item) use (&$data, $instance) {
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
     * {@inheritdoc}
     */
    public function getDataForCreation($id)
    {
        $instance = $this->getBuilder()->with('allocation', 'allocations', 'pack', 'option.service')
                         ->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return $instance;
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
     * {@inheritdoc}
     */
    public function getDaemonServiceData($id)
    {
        $instance = $this->getBuilder()->with('option.service', 'pack')->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return [
            'type' => $instance->option->service->folder,
            'option' => $instance->option->tag,
            'pack' => (! is_null($instance->pack_id)) ? $instance->pack->uuid : null,
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

        $instance = $this->getBuilder()->with('service', 'node')->where(function ($query) use ($uuid) {
            $query->where('uuidShort', $uuid)->orWhere('uuid', $uuid);
        })->first($this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }
}
