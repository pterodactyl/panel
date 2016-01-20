<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
