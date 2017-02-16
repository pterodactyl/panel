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
        return view('admin.nodes.index', [
            'nodes' => Models\Node::with('location')->withCount('servers')->paginate(20),
        ]);
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
        $node->setRelation('allocations', $node->allocations()->paginate(40));

        return view('admin.nodes.view', [
            'node' => $node,
            'stats' => Models\Server::select(DB::raw('SUM(memory) as memory, SUM(disk) as disk'))->where('node_id', $node->id)->first(),
            'locations' => Models\Location::all(),
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
                'daemonBase', 'daemonSFTP',
                'daemonListen', 'reset_secret',
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

    public function deallocateSingle(Request $request, $node, $allocation)
    {
        $query = Models\Allocation::where('node', $node)->whereNull('server_id')->where('id', $allocation)->delete();
        if ((int) $query === 0) {
            return response()->json([
                'error' => 'Unable to find an allocation matching those details to delete.',
            ], 400);
        }

        return response('', 204);
    }

    public function deallocateBlock(Request $request, $node)
    {
        $query = Models\Allocation::where('node', $node)->whereNull('server_id')->where('ip', $request->input('ip'))->delete();
        if ((int) $query === 0) {
            Alert::danger('There was an error while attempting to delete allocations on that IP.')->flash();

            return redirect()->route('admin.nodes.view', [
                'id' => $node,
                'tab' => 'tab_allocations',
            ]);
        }
        Alert::success('Deleted all unallocated ports for <code>' . $request->input('ip') . '</code>.')->flash();

        return redirect()->route('admin.nodes.view', [
            'id' => $node,
            'tab' => 'tab_allocation',
        ]);
    }

    public function setAlias(Request $request, $node)
    {
        if (! $request->input('allocation')) {
            return response('Missing required parameters.', 422);
        }

        try {
            $update = Models\Allocation::findOrFail($request->input('allocation'));
            $update->ip_alias = (empty($request->input('alias'))) ? null : $request->input('alias');
            $update->save();

            return response('', 204);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function getAllocationsJson(Request $request, $id)
    {
        $allocations = Models\Allocation::select('ip')->where('node', $id)->groupBy('ip')->get();

        return response()->json($allocations);
    }

    public function postAllocations(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'allocate_ip.*' => 'required|string',
            'allocate_port.*' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.nodes.view', [
                'id' => $id,
                'tab' => 'tab_allocation',
            ])->withErrors($validator->errors())->withInput();
        }

        $processedData = [];
        foreach ($request->input('allocate_ip') as $ip) {
            if (! array_key_exists($ip, $processedData)) {
                $processedData[$ip] = [];
            }
        }

        foreach ($request->input('allocate_port') as $portid => $ports) {
            if (array_key_exists($portid, $request->input('allocate_ip'))) {
                $json = json_decode($ports);
                if (json_last_error() === 0 && ! empty($json)) {
                    foreach ($json as &$parsed) {
                        array_push($processedData[$request->input('allocate_ip')[$portid]], $parsed->value);
                    }
                }
            }
        }

        try {
            $node = new NodeRepository;
            $node->addAllocations($id, $processedData);
            Alert::success('Successfully added new allocations to this node.')->flash();
        } catch (DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An unhandled exception occured while attempting to add allocations this node. Please try again.')->flash();
        } finally {
            return redirect()->route('admin.nodes.view', [
                'id' => $id,
                'tab' => 'tab_allocation',
            ]);
        }
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
