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

namespace Pterodactyl\Services;

use Pterodactyl\Models\Location;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Model\DataValidationException;

class LocationService
{
    /**
     * @var \Pterodactyl\Models\Location
     */
    protected $model;

    /**
     * LocationService constructor.
     *
     * @param \Pterodactyl\Models\Location $location
     */
    public function __construct(Location $location)
    {
        $this->model = $location;
    }

    /**
     * Create the location in the database and return it.
     *
     * @param  array $data
     * @return \Pterodactyl\Models\Location
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create(array $data)
    {
        $location = $this->model->newInstance($data);

        if (! $location->save()) {
            throw new DataValidationException($location->getValidator());
        }

        return $location;
    }

    /**
     * Update location model in the DB.
     *
     * @param  int   $id
     * @param  array $data
     * @return \Pterodactyl\Models\Location
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function update($id, array $data)
    {
        $location = $this->model->findOrFail($id)->fill($data);

        if (! $location->save()) {
            throw new DataValidationException($location->getValidator());
        }

        return $location;
    }

    /**
     * Delete a model from the DB.
     *
     * @param  int  $id
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete($id)
    {
        $location = $this->model->withCount('nodes')->findOrFail($id);

        if ($location->nodes_count > 0) {
            throw new DisplayException('Cannot delete a location that has nodes assigned to it.');
        }

        return $location->delete();
    }
}
