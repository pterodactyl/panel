<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Controllers\Admin;

use DB;
use Log;
use Alert;
use Carbon;
use Validator;
use Javascript;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\NodeRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class NodesController extends Controller
{
    /**
     * Controller Constructor.
     */
    public function __construct()
    {
        //
    }

    public function getScript(Request $request, $id)
    {
        return response()->view('admin.nodes.remote.deploy', [
            'node' => Models\Node::findOrFail($id),
        ])->header('Content-Type', 'text/plain');
    }

    public function getIndex(Request $request)
    {
        $nodes = Models\Node::with('location')->withCount('servers');

        if (! is_null($request->input('query'))) {
            $nodes->search($request->input('query'));
        }

        return view('admin.nodes.index', ['nodes' => $nodes->paginate(25)]);
    }

    public function getNew(Request $request)
    {
        if (! Models\Location::all()->count()) {
            Alert::warning('You must add a location before you can add a new node.')->flash();

            return redirect()->route('admin.locations');
        }

        return view('admin.nodes.new', [
            'locations' => Models\Location::all(),
        ]);
    }

    public function postNew(Request $request)
    {
        try {
            $repo = new NodeRepository;
            $node = $repo->create($request->only([
                'name', 'location_id', 'public',
                'fqdn', 'scheme', 'memory',
                'memory_overallocate', 'disk',
                'disk_overallocate', 'daemonBase',
                'daemonSFTP', 'daemonListen',
            ]));
            Alert::success('Successfully created new node that can be configured automatically on your remote machine by visiting the configuration tab. <strong>Before you can add any servers you need to first assign some IP addresses and ports.</strong>')->flash();

            return redirect()->route('admin.nodes.view', [
                'id' => $node->id,
                'tab' => 'tab_allocation',
            ]);
        } catch (DisplayValidationException $e) {
            return redirect()->route('admin.nodes.new')->withErrors(json_decode($e->getMessage()))->withInput();
        } catch (DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An unhandled exception occured while attempting to add this node. Please try again.')->flash();
        }

        return redirect()->route('admin.nodes.new')->withInput();
    }

    public function getView(Request $request, $id)
    {
        $node = Models\Node::with(
            'servers.user', 'servers.service',
            'servers.allocations', 'location'
        )->findOrFail($id);
        $node->setRelation('allocations', $node->allocations()->with('server')->paginate(40));

        return view('admin.nodes.view', [
            'node' => $node,
            'stats' => Models\Server::select(DB::raw('SUM(memory) as memory, SUM(disk) as disk'))->where('node_id', $node->id)->first(),
            'locations' => Models\Location::all(),
        ]);
    }

    /**
     * Shows the index overview page for a specific node.
     *
     * @param  Request $request
     * @param  int     $id      The ID of the node to display information for.
     *
     * @return \Illuminate\View\View
     */
    public function viewIndex(Request $request, $id)
    {
        $node = Models\Node::with('location')->withCount('servers')->findOrFail($id);
        $stats = collect(
            Models\Server::select(
                DB::raw('SUM(memory) as memory, SUM(disk) as disk')
            )->where('node_id', $node->id)->first()
        )->mapWithKeys(function ($item, $key) use ($node) {
            $percent = ($item / $node->{$key}) * 100;

            return [$key => [
                'value' => $item,
                'percent' => $percent,
                'css' => ($percent <= 75) ? 'green' : (($percent > 90) ? 'red' : 'yellow'),
            ]];
        })->toArray();

        return view('admin.nodes.view.index', ['node' => $node, 'stats' => $stats]);
    }

    /**
     * Shows the settings page for a specific node.
     *
     * @param  Request $request
     * @param  int     $id      The ID of the node to display information for.
     *
     * @return \Illuminate\View\View
     */
    public function viewSettings(Request $request, $id)
    {
        return view('admin.nodes.view.settings', [
            'node' => Models\Node::findOrFail($id),
            'locations' => Models\Location::all(),
        ]);
    }

    /**
     * Shows the configuration page for a specific node.
     *
     * @param  Request $request
     * @param  int     $id      The ID of the node to display information for.
     *
     * @return \Illuminate\View\View
     */
    public function viewConfiguration(Request $request, $id)
    {
        return view('admin.nodes.view.configuration', [
            'node' => Models\Node::findOrFail($id),
        ]);
    }

    /**
     * Shows the allocation page for a specific node.
     *
     * @param  Request $request
     * @param  int     $id      The ID of the node to display information for.
     *
     * @return \Illuminate\View\View
     */
    public function viewAllocation(Request $request, $id)
    {
        $node = Models\Node::findOrFail($id);
        $node->setRelation('allocations', $node->allocations()->orderBy('ip', 'asc')->orderBy('port', 'asc')->with('server')->paginate(50));

        Javascript::put([
            'node' => collect($node)->only(['id']),
        ]);

        return view('admin.nodes.view.allocation', ['node' => $node]);
    }

    /**
     * Shows the server listing page for a specific node.
     *
     * @param  Request $request
     * @param  int     $id      The ID of the node to display information for.
     *
     * @return \Illuminate\View\View
     */
    public function viewServers(Request $request, $id)
    {
        $node = Models\Node::with('servers.user', 'servers.service', 'servers.option')->findOrFail($id);
        Javascript::put([
            'node' => collect($node->makeVisible('daemonSecret'))->only(['scheme', 'fqdn', 'daemonListen', 'daemonSecret']),
        ]);

        return view('admin.nodes.view.servers', [
            'node' => $node,
        ]);
    }

    public function postView(Request $request, $id)
    {
        try {
            $node = new NodeRepository;
            $node->update($id, $request->only([
                'name', 'location_id', 'public',
                'fqdn', 'scheme', 'memory',
                'memory_overallocate', 'disk',
                'disk_overallocate', 'upload_size',
                'daemonSFTP', 'daemonListen', 'reset_secret',
            ]));
            Alert::success('Successfully update this node\'s information. If you changed any daemon settings you will need to restart it now.')->flash();

            return redirect()->route('admin.nodes.view', [
                'id' => $id,
                'tab' => 'tab_settings',
            ]);
        } catch (DisplayValidationException $e) {
            return redirect()->route('admin.nodes.view', $id)->withErrors(json_decode($e->getMessage()))->withInput();
        } catch (DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An unhandled exception occured while attempting to edit this node. Please try again.')->flash();
        }

        return redirect()->route('admin.nodes.view', [
            'id' => $id,
            'tab' => 'tab_settings',
        ])->withInput();
    }

    /**
     * Removes a single allocation from a node.
     *
     * @param  Request $request
     * @param  integer $node
     * @param  integer $allocation [description]
     * @return mixed
     */
    public function allocationRemoveSingle(Request $request, $node, $allocation)
    {
        $query = Models\Allocation::where('node_id', $node)->whereNull('server_id')->where('id', $allocation)->delete();
        if ($query < 1) {
            return response()->json([
                'error' => 'Unable to find an allocation matching those details to delete.',
            ], 400);
        }

        return response('', 204);
    }

    /**
     * Remove all allocations for a specific IP at once on a node.
     *
     * @param  Request $request
     * @param  integer  $node
     * @return mixed
     */
    public function allocationRemoveBlock(Request $request, $node)
    {
        $query = Models\Allocation::where('node_id', $node)->whereNull('server_id')->where('ip', $request->input('ip'))->delete();
        if ($query < 1) {
            Alert::danger('There was an error while attempting to delete allocations on that IP.')->flash();
        } else {
            Alert::success('Deleted all unallocated ports for <code>' . $request->input('ip') . '</code>.')->flash();
        }

        return redirect()->route('admin.nodes.view.allocation', $node);
    }

    /**
     * Sets an alias for a specific allocation on a node.
     *
     * @param  Request $request
     * @param  integer $node
     * @return mixed
     */
    public function allocationSetAlias(Request $request, $node)
    {
        if (! $request->input('allocation_id')) {
            return response('Missing required parameters.', 422);
        }

        try {
            $update = Models\Allocation::findOrFail($request->input('allocation_id'));
            $update->ip_alias = (empty($request->input('alias'))) ? null : $request->input('alias');
            $update->save();

            return response('', 204);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Creates new allocations on a node.
     *
     * @param  Request $request
     * @param  integer  $node
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createAllocation(Request $request, $node)
    {
        $repo = new NodeRepository;

        try {
            $repo->addAllocations($node, $request->intersect(['allocation_ip', 'allocation_alias', 'allocation_ports']));
            Alert::success('Successfully added new allocations!')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.nodes.view.allocation', $node)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attempting to add allocations this node. This error has been logged.')->flash();
        }

        return redirect()->route('admin.nodes.view.allocation', $node);
    }

    public function getAllocationsJson(Request $request, $id)
    {
        $allocations = Models\Allocation::select('ip')->where('node_id', $id)->groupBy('ip')->get();

        return response()->json($allocations);
    }

    public function deleteNode(Request $request, $id)
    {
        try {
            $repo = new NodeRepository;
            $repo->delete($id);
            Alert::success('Successfully deleted the requested node from the panel.')->flash();

            return redirect()->route('admin.nodes');
        } catch (DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An unhandled exception occured while attempting to delete this node. Please try again.')->flash();
        }

        return redirect()->route('admin.nodes.view', [
            'id' => $id,
            'tab' => 'tab_delete',
        ]);
    }

    public function getConfigurationToken(Request $request, $id)
    {
        // Check if Node exists. Will lead to 404 if not.
        Models\Node::findOrFail($id);

        // Create a token
        $token = new Models\NodeConfigurationToken();
        $token->node = $id;
        $token->token = str_random(32);
        $token->expires_at = Carbon::now()->addMinutes(5); // Expire in 5 Minutes
        $token->save();

        $token_response = [
            'token' => $token->token,
            'expires_at' => $token->expires_at->toDateTimeString(),
        ];

        return response()->json($token_response, 200);
    }
}
