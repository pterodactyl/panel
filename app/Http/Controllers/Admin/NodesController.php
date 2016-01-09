<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Alert;
use Debugbar;
use Log;
use DB;

use Pterodactyl\Models;
use Pterodactyl\Repositories\NodeRepository;

use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NodesController extends Controller
{

    /**
     * Controller Constructor
     */
    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.nodes.index', [
            'nodes' => Models\Node::select(
                'nodes.*',
                'locations.long as a_locationName',
                DB::raw('(SELECT COUNT(*) FROM servers WHERE servers.node = nodes.id) as a_serverCount')
            )->join('locations', 'nodes.location', '=', 'locations.id')->paginate(20),
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.nodes.new', [
            'locations' => Models\Location::all()
        ]);
    }

    public function postNew(Request $request)
    {
        try {
            $node = new NodeRepository;
            $new = $node->create($request->except([
                '_token'
            ]));
            Alert::success('Successfully created new node. You should allocate some IP addresses to it now.')->flash();
            return redirect()->route('admin.nodes.view', [
                'id' => $new
            ]);
        } catch (\Pterodactyl\Exceptions\DisplayValidationException $e) {
            return redirect()->route('admin.nodes.new')->withErrors(json_decode($e->getMessage()))->withInput();
        } catch (\Pterodactyl\Exceptions\DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An unhandled exception occured while attempting to add this node. Please try again.')->flash();
        }
        return redirect()->route('admin.nodes.new')->withInput();
    }

    public function getView(Request $request, $id)
    {
        $node = Models\Node::findOrFail($id);
        $allocations = [];
        $alloc = Models\Allocation::select('ip', 'port', 'assigned_to')->where('node', $node->id)->get();
        if ($alloc) {
            foreach($alloc as &$alloc) {
                if (!array_key_exists($alloc->ip, $allocations)) {
                    $allocations[$alloc->ip] = [[
                        'port' => $alloc->port,
                        'assigned_to' => $alloc->assigned_to
                    ]];
                } else {
                    array_push($allocations[$alloc->ip], [
                        'port' => $alloc->port,
                        'assigned_to' => $alloc->assigned_to
                    ]);
                }
            }
        }
        return view('admin.nodes.view', [
            'node' => $node,
            'servers' => Models\Server::select('servers.*', 'users.email as a_ownerEmail', 'services.name as a_serviceName')
                ->join('users', 'users.id', '=', 'servers.owner')
                ->join('services', 'services.id', '=', 'servers.service')
                ->where('node', $id)->paginate(10),
            'stats' => Models\Server::select(DB::raw('SUM(memory) as memory, SUM(disk) as disk'))->where('node', $node->id)->first(),
            'locations' => Models\Location::all(),
            'allocations' => json_decode(json_encode($allocations), false),
        ]);
    }

    public function postView(Request $request, $id)
    {
        try {
            $node = new NodeRepository;
            $node->update($id, $request->except([
                '_token'
            ]));
            Alert::success('Successfully update this node\'s information. If you changed any daemon settings you will need to restart it now.')->flash();
            return redirect()->route('admin.nodes.view', [
                'id' => $id,
                'tab' => 'tab_settings'
            ]);
        } catch (\Pterodactyl\Exceptions\DisplayValidationException $e) {
            return redirect()->route('admin.nodes.view', $id)->withErrors(json_decode($e->getMessage()))->withInput();
        } catch (\Pterodactyl\Exceptions\DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An unhandled exception occured while attempting to edit this node. Please try again.')->flash();
        }
        return redirect()->route('admin.nodes.view', [
            'id' => $id,
            'tab' => 'tab_settings'
        ])->withInput();
    }

    public function deletePortAllocation(Request $request, $id, $ip, $port)
    {
        $allocation = Models\Allocation::where('node', $id)->whereNull('assigned_to')->where('ip', $ip)->where('port', $port)->first();
        if (!$allocation) {
            return response()->json([
                'error' => 'Unable to find an allocation matching those details to delete.'
            ], 400);
        }
        $allocation->delete();
        return response('', 204);
    }

}
