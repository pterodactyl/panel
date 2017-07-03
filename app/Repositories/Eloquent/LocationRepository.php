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
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class LocationRepository extends EloquentRepository implements LocationRepositoryInterface
{
    /**
     * @var string
     */
    protected $searchTerm;

    /**
     * Setup model.
     *
     * @return string
     */
    public function model()
    {
        return Location::class;
    }

    /**
     * Setup the model for search abilities.
     *
     * @param  $term
     * @return $this
     */
    public function search($term)
    {
        if (empty($term)) {
            return $this;
        }

        $clone = clone $this;
        $clone->searchTerm = $term;

        return $clone;
    }

    /**
     * Delete a location only if there are no nodes attached to it.
     *
     * @param  $id
     * @return bool|mixed|null
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
}
