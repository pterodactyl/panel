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

namespace Pterodactyl\Repositories;

use Validator;
use Pterodactyl\Models\Location;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class LocationRepository
{
    /**
     * Creates a new location on the system.
     *
     * @param  array  $data
     * @return \Pterodactyl\Models\Location
     *
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'short' => 'required|string|between:1,60|unique:locations,short',
            'long' => 'required|string|between:1,255',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        return Location::create([
            'long' => $data['long'],
            'short' => $data['short'],
        ]);
    }

    /**
     * Modifies a location.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Pterodactyl\Models\Location
     *
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function update($id, array $data)
    {
        $location = Location::findOrFail($id);

        $validator = Validator::make($data, [
            'short' => 'sometimes|required|string|between:1,60|unique:locations,short,' . $location->id,
            'long' => 'sometimes|required|string|between:1,255',
        ]);

        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        $location->fill($data)->save();

        return $location;
    }

    /**
     * Deletes a location from the system.
     *
     * @param  int  $id
     * @return void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete($id)
    {
        $location = Location::withCount('nodes')->findOrFail($id);

        if ($location->nodes_count > 0) {
            throw new DisplayException('Cannot delete a location that has nodes assigned to it.');
        }

        $location->delete();
    }
}
