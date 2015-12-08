<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Debugbar;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\ServiceOptions;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AjaxController extends Controller
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

    /**
     * Returns a JSON tree of all avaliable nodes in a given location.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function postNewServerGetNodes(Request $request)
    {

        if(!$request->input('location')) {
            return response()->json([
                'error' => 'Missing location in request.'
            ], 500);
        }

        return response()->json(Node::select('id', 'name', 'public')->where('location', $request->input('location'))->get());

    }

    /**
     * Returns a JSON tree of all avaliable IPs and Ports on a given node.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function postNewServerGetIps(Request $request)
    {

        if(!$request->input('node')) {
            return response()->json([
                'error' => 'Missing node in request.'
            ], 500);
        }

        $ips = Allocation::where('node', $request->input('node'))->whereNull('assigned_to')->get();
        $listing = [];

        foreach($ips as &$ip) {
            if (array_key_exists($ip->ip, $listing)) {
                $listing[$ip->ip] = array_merge($listing[$ip->ip], [$ip->port]);
            } else {
                $listing[$ip->ip] = [$ip->port];
            }
        }
        return response()->json($listing);

    }

    /**
     * Returns a JSON tree of all avaliable options for a given service.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function postNewServerServiceOptions(Request $request)
    {

        if(!$request->input('service')) {
            return response()->json([
                'error' => 'Missing service in request.'
            ], 500);
        }

        return response()->json(ServiceOptions::select('id', 'name')->where('parent_service', $request->input('service'))->orderBy('name', 'asc')->get());

    }

}
