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
        return view('admin.nodes.view', [
            'node' => $node
        ]);
    }

}
