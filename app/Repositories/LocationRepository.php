<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Pterodactyl\Repositories;

use Validator;

use Pterodactyl\Models;
use Pterodactyl\Exceptions\DisplayValidationException;

class LocationRepository
{

    public function __construct()
    {
        //
    }

    /**
     * Creates a new location on the system.
     * @param  array  $data
     * @throws Pterodactyl\Exceptions\DisplayValidationException
     * @return integer
     */
    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'short' => 'required|regex:/^[a-z0-9_.-]{1,10}$/i|unique:locations,short',
            'long' => 'required|string|min:1|max:255'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $location = new Models\Location;
        $location->fill([
            'long' => $data['long'],
            'short' => $data['short']
        ]);
        $location->save();

        return $location->id;
    }

    /**
     * Modifies a location based on the fields passed in $data.
     * @param  integer $id
     * @param  array   $data
     * @throws Pterodactyl\Exceptions\DisplayValidationException
     * @return boolean
     */
    public function edit($id, array $data)
    {
        $validator = Validator::make($data, [
            'short' => 'regex:/^[a-z0-9_.-]{1,10}$/i',
            'long' => 'string|min:1|max:255'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $location = Models\Location::findOrFail($id);

        if (isset($data['short'])) {
            $location->short = $data['short'];
        }

        if (isset($data['long'])) {
            $location->long = $data['long'];
        }

        return $location->save();
    }
}
