<?php

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
