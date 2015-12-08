<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Debugbar;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Service;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ServersController extends Controller
{

    /**
     * Controller Constructor
     */
    public function __construct()
    {

        // All routes in this controller are protected by the authentication middleware.
        $this->middleware('auth');
        $this->middleware('admin');

    }

    public function getIndex(Request $request)
    {
        return view('admin.servers.index', [
            'servers' => Server::select('servers.*', 'nodes.name as a_nodeName', 'users.email as a_ownerEmail')
                ->join('nodes', 'servers.node', '=', 'nodes.id')
                ->join('users', 'servers.owner', '=', 'users.id')
                ->paginate(20),
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.servers.new', [
            'locations' => Location::all(),
            'services' => Service::all()
        ]);
    }

    public function getView(Request $request, $id)
    {
        //
    }

}
