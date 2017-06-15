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
     * @throws \Throwable
     * @throws \Watson\Validating\ValidationException
     */
    public function create(array $data)
    {
        $location = $this->model->fill($data);
        $location->saveOrFail();

        return $location;
    }

    /**
     * Update location model in the DB.
     *
     * @param  \Pterodactyl\Models\Location $location
     * @param  array                        $data
     * @return \Pterodactyl\Models\Location
     *
     * @throws \Throwable
     * @throws \Watson\Validating\ValidationException
     */
    public function update(Location $location, array $data)
    {
        $location->fill($data)->saveOrFail();

        return $location;
    }

    /**
     * Delete a model from the DB.
     *
     * @param \Pterodactyl\Models\Location $location
     * @return bool
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(Location $location)
    {
        if ($location->nodes()->count() > 0) {
            throw new DisplayException('Cannot delete a location that has nodes assigned to it.');
        }

        return $location->delete();
    }
}
