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
