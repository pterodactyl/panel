<?php

namespace Pterodactyl\Http\Controllers\API;

use DB;
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
        $locations = Location::select('locations.*', DB::raw('GROUP_CONCAT(nodes.id) as nodes'))
            ->join('nodes', 'locations.id', '=', 'nodes.location')
            ->groupBy('locations.id')
            ->get();

        foreach($locations as &$location) {
            $location->nodes = explode(',', $location->nodes);
        }

        return $locations;
    }

}
