<?php
/**
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

namespace Pterodactyl\Services;

use DB;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Pterodactyl\Exceptions\AutoDeploymentException;

class DeploymentService
{
    /**
     * Eloquent model representing the allocation to use.
     *
     * @var \Pterodactyl\Models\Allocation
     */
    protected $allocation;

    /**
     * Amount of disk to be used by the server.
     *
     * @var int
     */
    protected $disk;

    /**
     * Amount of memory to be used by the sever.
     *
     * @var int
     */
    protected $memory;

    /**
     * Eloquent model representing the location to use.
     *
     * @var \Pterodactyl\Models\Location
     */
    protected $location;

    /**
     * Eloquent model representing the node to use.
     *
     * @var \Pterodactyl\Models\Node
     */
    protected $node;

    /**
     * Set the location to use when auto-deploying.
     *
     * @param  int|\Pterodactyl\Models\Location  $location
     * @return void
     */
    public function setLocation($location)
    {
        $this->location = ($location instanceof Location) ? $location : Location::with('nodes')->findOrFail($location);
        if (! $this->location->relationLoaded('nodes')) {
            $this->location->load('nodes');
        }

        if (count($this->location->nodes) < 1) {
            throw new AutoDeploymentException('The location provided does not contain any nodes and cannot be used.');
        }

        return $this;
    }

    /**
     * Set the node to use when auto-deploying.
     *
     * @param  int|\Pterodactyl\Models\Node  $node
     * @return void
     */
    public function setNode($node)
    {
        $this->node = ($node instanceof Node) ? $node : Node::findOrFail($node);
        if (! $this->node->relationLoaded('allocations')) {
            $this->node->load('allocations');
        }

        $this->setLocation($this->node->location);

        return $this;
    }

    /**
     * Set the amount of disk space to be used by the new server.
     *
     * @param  int  $disk
     * @return void
     */
    public function setDisk(int $disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set the amount of memory to be used by the new server.
     *
     * @param  int  $memory
     * @return void
     */
    public function setMemory(int $memory)
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Return a random location model.
     *
     * @param  array  $exclude
     * @return void;
     */
    protected function findLocation(array $exclude = [])
    {
        $location = Location::with('nodes')->whereNotIn('id', $exclude)->inRandomOrder()->first();

        if (! $location) {
            throw new AutoDeploymentException('Unable to locate a suitable location to select a node from.');
        }

        if (count($location->nodes) < 1) {
            return $this->findLocation(array_merge($exclude, [$location->id]));
        }

        $this->setLocation($location);
    }

    /**
     * Return a model instance of a random node.
     *
     * @return void;
     */
    protected function findNode(array $exclude = [])
    {
        if (! $this->location) {
            $this->setLocation($this->findLocation());
        }

        $select = $this->location->nodes->whereNotIn('id', $exclude);
        if (count($select) < 1) {
            throw new AutoDeploymentException('Unable to find a suitable node within the assigned location with enough space.');
        }

        // Check usage, select new node if necessary
        $this->setNode($select->random());
        if (! $this->checkNodeUsage()) {
            return $this->findNode(array_merge($exclude, [$this->node()->id]));
        }
    }

    /**
     * Checks that a node's allocation limits will not be passed
     * with the assigned limits.
     *
     * @return bool
     */
    protected function checkNodeUsage()
    {
        if (! $this->disk && ! $this->memory) {
            return true;
        }

        $totals = Server::select(DB::raw('SUM(memory) as memory, SUM(disk) as disk'))->where('node_id', $this->node()->id)->first();

        if ($this->memory) {
            $limit = ($this->node()->memory * (1 + ($this->node()->memory_overallocate / 100)));

            if (($totals->memory + $this->memory) > $limit) {
                return false;
            }
        }

        if ($this->disk) {
            $limit = ($this->node()->disk * (1 + ($this->node()->disk_overallocate / 100)));

            if (($totals->disk + $this->disk) > $limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the assigned node for this auto-deployment.
     *
     * @return \Pterodactyl\Models\Node
     */
    public function node()
    {
        return $this->node;
    }

    /**
     * Return the assigned location for this auto-deployment.
     *
     * @return \Pterodactyl\Models\Location
     */
    public function location()
    {
        return $this->location;
    }

    /**
     * Return the assigned location for this auto-deployment.
     *
     * @return \Pterodactyl\Models\Allocation
     */
    public function allocation()
    {
        return $this->allocation;
    }

    /**
     * Select and return the node to be used by the auto-deployment system.
     *
     * @return void
     */
    public function select()
    {
        if (! $this->node) {
            $this->findNode();
        }

        // Set the Allocation
        $this->allocation = $this->node()->allocations->where('server_id', null)->random();
        if (! $this->allocation) {
            throw new AutoDeploymentException('Unable to find a suitable allocation to assign to this server.');
        }
    }
}
