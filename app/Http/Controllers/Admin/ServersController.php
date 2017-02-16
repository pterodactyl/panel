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

use Log;
use Alert;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\ServerRepository;
use Pterodactyl\Repositories\DatabaseRepository;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServersController extends Controller
{
    /**
     * Controller Constructor.
     */
    public function __construct()
    {
        //
    }

    public function getIndex(Request $request)
    {
        return view('admin.servers.index', [
            'servers' => Models\Server::withTrashed()->with('node', 'user')->paginate(25),
        ]);
    }

    public function getNew(Request $request)
    {
        return view('admin.servers.new', [
            'locations' => Models\Location::all(),
            'services' => Models\Service::all(),
        ]);
    }

    public function getView(Request $request, $id)
    {
        $server = Models\Server::withTrashed()->with(
            'user', 'option.variables', 'variables',
            'node.allocations', 'databases.host'
        )->findOrFail($id);

        $server->option->variables->transform(function ($item, $key) use ($server) {
            $item->server_value = $server->variables->where('variable_id', $item->id)->pluck('variable_value')->first();

            return $item;
        });

        return view('admin.servers.view', [
            'server' => $server,
            'assigned' => $server->node->allocations->where('server_id', $server->id)->sortBy('port')->sortBy('ip'),
            'unassigned' => $server->node->allocations->where('server_id', null)->sortBy('port')->sortBy('ip'),
            'db_servers' => Models\DatabaseServer::all(),
        ]);
    }

    public function postNewServer(Request $request)
    {
        try {
            $server = new ServerRepository;
            $response = $server->create($request->except('_token'));

            return redirect()->route('admin.servers.view', ['id' => $response->id]);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();

            return redirect()->route('admin.servers.new')->withInput();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to add this server. Please try again.')->flash();

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
        if (! $request->input('location')) {
            return response()->json([
                'error' => 'Missing location in request.',
            ], 500);
        }

        return response()->json(Models\Node::select('id', 'name', 'public')->where('location_id', $request->input('location'))->get());
    }

    /**
     * Returns a JSON tree of all avaliable IPs and Ports on a given node.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function postNewServerGetIps(Request $request)
    {
        if (! $request->input('node')) {
            return response()->json([
                'error' => 'Missing node in request.',
            ], 500);
        }

        $ips = Models\Allocation::where('node_id', $request->input('node'))->whereNull('server_id')->get();
        $listing = [];

        foreach ($ips as &$ip) {
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
    public function postNewServerServiceOption(Request $request)
    {
        if (! $request->input('service')) {
            return response()->json([
                'error' => 'Missing service in request.',
            ], 500);
        }

        $service = Models\Service::select('executable', 'startup')->where('id', $request->input('service'))->first();

        return response()->json(Models\ServiceOption::select('id', 'name', 'docker_image')->where('service_id', $request->input('service'))->orderBy('name', 'asc')->get());
    }

    /**
     * Returns a JSON tree of all avaliable variables for a given service option.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function postNewServerOptionDetails(Request $request)
    {
        if (! $request->input('option')) {
            return response()->json([
                'error' => 'Missing option in request.',
            ], 500);
        }

        $option = Models\ServiceOption::with('variables')->with(['packs' => function ($query) {
            $query->where('selectable', true);
        }])->findOrFail($request->input('option'));

        return response()->json([
            'packs' => $option->packs,
            'variables' => $option->variables,
            'exec' => $option->display_executable,
            'startup' => $option->display_startup,
        ]);
    }

    public function postUpdateServerDetails(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->updateDetails($id, [
                'owner' => $request->input('owner'),
                'name' => $request->input('name'),
                'reset_token' => ($request->input('reset_token', false) === 'on') ? true : false,
            ]);

            Alert::success('Server details were successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_details',
            ])->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to update this server. Please try again.')->flash();
        }

        return redirect()->route('admin.servers.view', [
            'id' => $id,
            'tab' => 'tab_details',
        ])->withInput();
    }

    public function postUpdateContainerDetails(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->updateContainer($id, ['image' => $request->input('docker_image')]);
            Alert::success('Successfully updated this server\'s docker image.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_details',
            ])->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to update this server\'s docker image. Please try again.')->flash();
        }

        return redirect()->route('admin.servers.view', [
            'id' => $id,
            'tab' => 'tab_details',
        ]);
    }

    public function postUpdateServerToggleBuild(Request $request, $id)
    {
        $server = Models\Server::with('node')->findOrFail($id);

        try {
            $res = $server->node->guzzleClient([
                'X-Access-Server' => $server->uuid,
                'X-Access-Token' => $node->daemonSecret,
            ])->request('POST', '/server/rebuild');
            Alert::success('A rebuild has been queued successfully. It will run the next time this server is booted.')->flash();
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            Log::warning($ex);
            Alert::danger('An error occured while attempting to toggle a rebuild.')->flash();
        }

        return redirect()->route('admin.servers.view', [
            'id' => $id,
            'tab' => 'tab_manage',
        ]);
    }

    public function postUpdateServerUpdateBuild(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->changeBuild($id, $request->only([
                'default', 'add_additional',
                'remove_additional', 'memory',
                'swap', 'io', 'cpu',
            ]));
            Alert::success('Server details were successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_build',
            ])->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();

            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_build',
            ]);
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to add this server. Please try again.')->flash();
        }

        return redirect()->route('admin.servers.view', [
            'id' => $id,
            'tab' => 'tab_build',
        ]);
    }

    public function deleteServer(Request $request, $id, $force = null)
    {
        try {
            $server = new ServerRepository;
            $server->deleteServer($id, $force);
            Alert::success('Server has been marked for deletion on the system.')->flash();

            return redirect()->route('admin.servers');
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to delete this server. Please try again.')->flash();
        }

        return redirect()->route('admin.servers.view', [
            'id' => $id,
            'tab' => 'tab_delete',
        ]);
    }

    public function postToggleInstall(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->toggleInstall($id);
            Alert::success('Server status was successfully toggled.')->flash();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to toggle this servers status.')->flash();
        } finally {
            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_manage',
            ]);
        }
    }

    public function postUpdateServerStartup(Request $request, $id)
    {
        try {
            $server = new ServerRepository;
            $server->updateStartup($id, $request->except([
                '_token',
            ]), true);
            Alert::success('Server startup variables were successfully updated.')->flash();
        } catch (\Pterodactyl\Exceptions\DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An unhandled exception occured while attemping to update startup variables for this server. Please try again.')->flash();
        } finally {
            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_startup',
            ])->withInput();
        }
    }

    public function postDatabase(Request $request, $id)
    {
        try {
            $repo = new DatabaseRepository;
            $repo->create($id, $request->only([
                'db_server', 'database', 'remote',
            ]));
            Alert::success('Added new database to this server.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_database',
            ])->withInput()->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An exception occured while attempting to add a new database for this server.')->flash();
        }

        return redirect()->route('admin.servers.view', [
            'id' => $id,
            'tab' => 'tab_database',
        ])->withInput();
    }

    public function postSuspendServer(Request $request, $id)
    {
        try {
            $repo = new ServerRepository;
            $repo->suspend($id);
            Alert::success('Server has been suspended on the system. All running processes have been stopped and will not be startable until it is un-suspended.');
        } catch (DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An unhandled exception occured while attemping to suspend this server. Please try again.')->flash();
        } finally {
            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_manage',
            ]);
        }
    }

    public function postUnsuspendServer(Request $request, $id)
    {
        try {
            $repo = new ServerRepository;
            $repo->unsuspend($id);
            Alert::success('Server has been unsuspended on the system. Access has been re-enabled.');
        } catch (DisplayException $e) {
            Alert::danger($e->getMessage())->flash();
        } catch (\Exception $e) {
            Log::error($e);
            Alert::danger('An unhandled exception occured while attemping to unsuspend this server. Please try again.')->flash();
        } finally {
            return redirect()->route('admin.servers.view', [
                'id' => $id,
                'tab' => 'tab_manage',
            ]);
        }
    }

    public function postQueuedDeletionHandler(Request $request, $id)
    {
        try {
            $repo = new ServerRepository;
            if (! is_null($request->input('cancel'))) {
                $repo->cancelDeletion($id);
                Alert::success('Server deletion has been cancelled. This server will remain suspended until you unsuspend it.')->flash();

                return redirect()->route('admin.servers.view', $id);
            } elseif (! is_null($request->input('delete'))) {
                $repo->deleteNow($id);
                Alert::success('Server was successfully deleted from the system.')->flash();

                return redirect()->route('admin.servers');
            } elseif (! is_null($request->input('force_delete'))) {
                $repo->deleteNow($id, true);
                Alert::success('Server was successfully force deleted from the system.')->flash();

                return redirect()->route('admin.servers');
            }
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();

            return redirect()->route('admin.servers.view', $id);
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled error occured while attempting to perform this action.')->flash();

            return redirect()->route('admin.servers.view', $id);
        }
    }
}
