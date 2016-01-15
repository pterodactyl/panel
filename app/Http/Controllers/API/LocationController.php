<?php

namespace Pterodactyl\Http\Controllers\API;

use Illuminate\Http\Request;
use Pterodactyl\Models\Location;

/**
 * @Resource("Servers")
 */
class LocationController extends BaseController
{

    public function __construct()
    {
        //
    }

    /**
     * List All Locations
     *
     * Lists all locations currently on the system.
     *
     * @Get("/locations")
     * @Versions({"v1"})
     * @Response(200)
     */
    public function getLocations(Request $request)
    {
        return Location::all();
    }

}
