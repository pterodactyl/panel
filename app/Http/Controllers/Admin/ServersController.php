<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Debugbar;
use Log;

use Pterodactyl\Models;
use Pterodactyl\Repositories\ServerRepository;

use Pterodactly\Exceptions\DisplayException;
use Pterodactly\Exceptions\DisplayValidationException;

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
            'servers' => Models\Server::select('servers.*', 'nodes.name as a_nodeName', 'users.email as a_ownerEmail')
                ->join('nodes', 'servers.node', '=', 'nodes.id')
                ->join('users', 'servers.owner', '=', 'users.id')
                ->paginate(20),
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.servers.new', [
            'locations' => Models\Location::all(),
            'services' => Models\Service::all()
        ]);
    }

    public function getView(Request $request, $id)
    {
        return view('admin.servers.view', [
            'server' => Models\Server::select(
                    'servers.*',
                    'nodes.name as a_nodeName',
                    'users.email as a_ownerEmail',
                    'locations.long as a_locationName',
                    'services.name as a_serviceName',
                    'service_options.name as a_servceOptionName'
                )->join('nodes', 'servers.node', '=', 'nodes.id')
                ->join('users', 'servers.owner', '=', 'users.id')
                ->join('locations', 'nodes.location', '=', 'locations.id')
                ->join('services', 'servers.service', '=', 'services.id')
                ->join('service_options', 'servers.option', '=', 'service_options.id')
                ->first()
        ]);
    }

    public function postNewServer(Request $request)
    {

        try {

            $server = new ServerRepository;
            $response = $server->create($request->all());

            return redirect()->route('admin.servers.view', [ 'id' => $response ]);

        } catch (\Exception $e) {

            if ($e instanceof \Pterodactyl\Exceptions\DisplayValidationException) {
                return redirect()->route('admin.servers.new')->withErrors(json_decode($e->getMessage()))->withInput();
            } else if ($e instanceof \Pterodactyl\Exceptions\DisplayException) {
                Alert::danger($e->getMessage())->flash();
            } else {
                Debugbar::addException($e);
                Alert::danger('An unhandled exception occured while attemping to add this server. Please try again.')->flash();
            }

            return redirect()->route('admin.servers.new')->withInput();

        }

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

        return response()->json(Models\Node::select('id', 'name', 'public')->where('location', $request->input('location'))->get());

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

        $ips = Models\Allocation::where('node', $request->input('node'))->whereNull('assigned_to')->get();
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

        $service = Models\Service::select('executable', 'startup')->where('id', $request->input('service'))->first();
        return response()->json([
            'exec' => $service->executable,
            'startup' => $service->startup,
            'options' => Models\ServiceOptions::select('id', 'name', 'docker_image')->where('parent_service', $request->input('service'))->orderBy('name', 'asc')->get()
        ]);

    }

    /**
     * Returns a JSON tree of all avaliable variables for a given service option.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function postNewServerServiceVariables(Request $request)
    {

        if(!$request->input('option')) {
            return response()->json([
                'error' => 'Missing option in request.'
            ], 500);
        }

        return response()->json(Models\ServiceVariables::where('option_id', $request->input('option'))->get());

    }

    public function postUpdateServerDetails(Request $request, $id)
    {

        try {

            $server = new ServerRepository;
            $server->updateDetails($id, [
                'owner' => $request->input('owner'),
                'name' => $request->input('name'),
                'reset_token' => ($request->input('reset_token', false) === 'on') ? true : false
            ]);

            Alert::success('Server details were successfully updated.')->flash();
            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_details'
            ]);

        } catch (\Exception $e) {

            if ($e instanceof \Pterodactyl\Exceptions\DisplayValidationException) {
                return redirect()->route('admin.servers.view', [
                    'id' => $id,
                    'tab' => 'tab_details'
                ])->withErrors(json_decode($e->getMessage()))->withInput();
            } else if ($e instanceof \Pterodactyl\Exceptions\DisplayException) {
                Alert::danger($e->getMessage())->flash();
            } else {
                Log::error($e);
                Alert::danger('An unhandled exception occured while attemping to add this server. Please try again.')->flash();
            }

            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_details'
            ])->withInput();

        }
    }

}
