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

use Pterodactyl\Models\Location;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Concerns\Searchable;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationRepository extends EloquentRepository implements LocationRepositoryInterface
{
    use Searchable;

    /**
     * @var string
     */
    protected $searchTerm;

    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Location::class;
    }

    /**
     * {@inheritdoc}
     * @todo remove this, do logic in service
     */
    public function deleteIfNoNodes($id)
    {
        $location = $this->getBuilder()->with('nodes')->find($id);

        if (! $location) {
            throw new RecordNotFoundException();
        }

        if ($location->nodes_count > 0) {
            throw new DisplayException('Cannot delete a location that has nodes assigned to it.');
        }

        return $location->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithDetails()
    {
        return $this->getBuilder()->withCount('nodes', 'servers')->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithNodes()
    {
        return $this->getBuilder()->with('nodes')->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getWithNodes($id)
    {
        $instance = $this->getBuilder()->with('nodes.servers')->find($id, $this->getColumns());

        if (! $instance) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }
}
