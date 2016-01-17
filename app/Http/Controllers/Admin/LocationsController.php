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

    public function deleteLocation(Request $request, $id)
    {
        $model = Models\Location::select(
            'locations.id',
            DB::raw('(SELECT COUNT(*) FROM nodes WHERE nodes.location = locations.id) as a_nodeCount'),
            DB::raw('(SELECT COUNT(*) FROM servers WHERE servers.node IN (SELECT nodes.id FROM nodes WHERE nodes.location = locations.id)) as a_serverCount')
        )->where('id', $id)->first();

        if (!$model) {
            return response()->json([
                'error' => 'No location with that ID exists on the system.'
            ], 404);
        }

        if ($model->a_nodeCount > 0 || $model->a_serverCount > 0) {
            return response()->json([
                'error' => 'You cannot remove a location that is currently assigned to a node or server.'
            ], 422);
        }

        $model->delete();
        return response('', 204);
    }

}
