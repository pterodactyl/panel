<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\API\Admin;

use Fractal;
use Illuminate\Http\Request;
use Pterodactyl\Models\Location;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Transformers\Admin\LocationTransformer;

class LocationController extends Controller
{
    /**
     * Controller to handle returning all locations on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $this->authorize('location-list', $request->apiKey());

        return Fractal::create()
            ->collection(Location::all())
            ->transformWith(new LocationTransformer($request))
            ->withResourceName('location')
            ->toArray();
    }
}
