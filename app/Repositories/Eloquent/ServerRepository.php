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
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Repositories\Eloquent\Attributes\SearchableRepository;

class ServerRepository extends SearchableRepository implements ServerRepositoryInterface
{
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
}
