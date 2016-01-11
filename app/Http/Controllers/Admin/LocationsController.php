<?php

namespace Pterodactyl\Http\Controllers\Admin;

use DB;

use Pterodactyl\Models;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationsController extends Controller
{

    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.locations.index', [
            'locations' => Models\Location::select(
                    'locations.*',
                    DB::raw('(SELECT COUNT(*) FROM nodes WHERE nodes.location = locations.id) as a_nodeCount'),
                    DB::raw('(SELECT COUNT(*) FROM servers WHERE servers.node IN (SELECT nodes.id FROM nodes WHERE nodes.location = locations.id)) as a_serverCount')
                )->paginate(20)
        ]);
    }

    public function postView(Request $request)
    {
        $location = Models\Location::findOrFail($request->input('location_id'));
    }

}
