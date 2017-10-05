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
use Javascript;
use Pterodactyl\Models;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\ServerRepository;
use Pterodactyl\Repositories\DatabaseRepository;
use Pterodactyl\Exceptions\AutoDeploymentException;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServersController extends Controller
{
    /**
     * Display the index page with all servers currently on the system.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $servers = Models\Server::with('node', 'user', 'allocation');

        if (! is_null($request->input('query'))) {
            $servers->search($request->input('query'));
        }

        return view('admin.servers.index', [
            'servers' => $servers->paginate(25),
        ]);
    }

    /**
     * Display create new server page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $services = Models\Service::with('options.packs', 'options.variables')->get();
        Javascript::put([
            'services' => $services->map(function ($item) {
                return array_merge($item->toArray(), [
                    'options' => $item->options->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
        ]);

        return view('admin.servers.new', [
            'locations' => Models\Location::all(),
            'services' => $services,
        ]);
    }

    /**
     * Create server controller method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Response\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $repo = new ServerRepository;
            $server = $repo->create($request->except('_token'));

            return redirect()->route('admin.servers.view', $server->id);
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.new')->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (AutoDeploymentException $ex) {
            Alert::danger('Auto-Deployment Exception: ' . $ex->getMessage())->flash();
        } catch (TransferException $ex) {
            Log::warning($ex);
            Alert::danger('A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.')->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to add this server. Please try again.')->flash();
        }

        return redirect()->route('admin.servers.new')->withInput();
    }

    /**
     * Returns a tree of all avaliable nodes in a given location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function nodes(Request $request)
    {
        $nodes = Models\Node::with('allocations')->where('location_id', $request->input('location'))->get();

        return $nodes->map(function ($item) {
            $filtered = $item->allocations->where('server_id', null)->map(function ($map) {
                return collect($map)->only(['id', 'ip', 'port']);
            });

            $item->ports = $filtered->map(function ($map) use ($item) {
                return [
                    'id' => $map['id'],
                    'text' => $map['ip'] . ':' . $map['port'],
                ];
            })->values();

            return [
                'id' => $item->id,
                'text' => $item->name,
                'allocations' => $item->ports,
            ];
        })->values();
    }

    /**
     * Display the index when viewing a specific server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function viewIndex(Request $request, $id)
    {
        return view('admin.servers.view.index', ['server' => Models\Server::findOrFail($id)]);
    }

    /**
     * Display the details page when viewing a specific server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function viewDetails(Request $request, $id)
    {
        $server = Models\Server::where('installed', 1)->findOrFail($id);

        return view('admin.servers.view.details', ['server' => $server]);
    }

    /**
     * Display the build details page when viewing a specific server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function viewBuild(Request $request, $id)
    {
        $server = Models\Server::where('installed', 1)->with('node.allocations')->findOrFail($id);

        return view('admin.servers.view.build', [
            'server' => $server,
            'assigned' => $server->node->allocations->where('server_id', $server->id)->sortBy('port')->sortBy('ip'),
            'unassigned' => $server->node->allocations->where('server_id', null)->sortBy('port')->sortBy('ip'),
        ]);
    }

    /**
     * Display startup configuration page for a server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function viewStartup(Request $request, $id)
    {
        $server = Models\Server::where('installed', 1)->with('option.variables', 'variables')->findOrFail($id);
        $server->option->variables->transform(function ($item, $key) use ($server) {
            $item->server_value = $server->variables->where('variable_id', $item->id)->pluck('variable_value')->first();

            return $item;
        });

        $services = Models\Service::with('options.packs', 'options.variables')->get();
        Javascript::put([
            'services' => $services->map(function ($item) {
                return array_merge($item->toArray(), [
                    'options' => $item->options->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
            'server_variables' => $server->variables->mapWithKeys(function ($item) {
                return ['env_' . $item->variable_id => [
                    'value' => $item->variable_value,
                ]];
            })->toArray(),
        ]);

        return view('admin.servers.view.startup', [
            'server' => $server,
            'services' => $services,
        ]);
    }

    /**
     * Display the database management page for a specific server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\View\View
     */
    public function viewDatabase(Request $request, $id)
    {
        $server = Models\Server::where('installed', 1)->with('databases.host')->findOrFail($id);

        return view('admin.servers.view.database', [
            'hosts' => Models\DatabaseHost::all(),
            'server' => $server,
        ]);
    }

    /**
     * Display the management page when viewing a specific server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function viewManage(Request $request, $id)
    {
        return view('admin.servers.view.manage', ['server' => Models\Server::findOrFail($id)]);
    }

    /**
     * Display the deletion page for a server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\View\View
     */
    public function viewDelete(Request $request, $id)
    {
        return view('admin.servers.view.delete', ['server' => Models\Server::findOrFail($id)]);
    }

    /**
     * Update the details for a server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setDetails(Request $request, $id)
    {
        $repo = new ServerRepository;
        try {
            $repo->updateDetails($id, array_merge(
                $request->only('description'),
                $request->intersect([
                    'owner_id', 'name', 'reset_token',
                ])
            ));

            Alert::success('Server details were successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.view.details', $id)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to update this server. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.details', $id)->withInput();
    }

    /**
     * Set the new docker container for a server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setContainer(Request $request, $id)
    {
        $repo = new ServerRepository;

        try {
            $repo->updateContainer($id, $request->intersect('docker_image'));

            Alert::success('Successfully updated this server\'s docker image.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.view.details', $id)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (TransferException $ex) {
            Log::warning($ex);
            Alert::danger('A TransferException occured while attempting to update the container image. Is the daemon online? This error has been logged.');
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to update this server\'s docker image. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.details', $id);
    }

    /**
     * Toggles the install status for a server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleInstall(Request $request, $id)
    {
        $repo = new ServerRepository;
        try {
            $repo->toggleInstall($id);

            Alert::success('Server install status was successfully toggled.')->flash();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to toggle this servers status. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.manage', $id);
    }

    /**
     * Reinstalls the server with the currently assigned pack and service.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reinstallServer(Request $request, $id)
    {
        $repo = new ServerRepository;
        try {
            $repo->reinstall($id);

            Alert::success('Server successfully marked for reinstallation.')->flash();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to perform this reinstallation. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.manage', $id);
    }

    /**
     * Setup a server to have a container rebuild.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rebuildContainer(Request $request, $id)
    {
        $server = Models\Server::with('node')->findOrFail($id);

        try {
            $server->node->guzzleClient([
                'X-Access-Server' => $server->uuid,
                'X-Access-Token' => $server->node->daemonSecret,
            ])->request('POST', '/server/rebuild');

            Alert::success('A rebuild has been queued successfully. It will run the next time this server is booted.')->flash();
        } catch (TransferException $ex) {
            Log::warning($ex);
            Alert::danger('A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.manage', $id);
    }

    /**
     * Manage the suspension status for a server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function manageSuspension(Request $request, $id)
    {
        $repo = new ServerRepository;
        $action = $request->input('action');

        if (! in_array($action, ['suspend', 'unsuspend'])) {
            Alert::danger('Invalid action was passed to function.')->flash();

            return redirect()->route('admin.servers.view.manage', $id);
        }

        try {
            $repo->toggleAccess($id, ($action === 'unsuspend'));

            Alert::success('Server has been ' . $action . 'ed.');
        } catch (TransferException $ex) {
            Log::warning($ex);
            Alert::danger('A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.')->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to ' . $action . ' this server. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.manage', $id);
    }

    /**
     * Update the build configuration for a server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBuild(Request $request, $id)
    {
        $repo = new ServerRepository;

        try {
            $repo->changeBuild($id, $request->intersect([
                'allocation_id', 'add_allocations', 'remove_allocations',
                'memory', 'swap', 'io', 'cpu', 'disk',
            ]));

            Alert::success('Server details were successfully updated.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.view.build', $id)->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (TransferException $ex) {
            Log::warning($ex);
            Alert::danger('A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.')->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to add this server. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.build', $id);
    }

    /**
     * Start the server deletion process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request, $id)
    {
        $repo = new ServerRepository;

        try {
            $repo->delete($id, $request->has('force_delete'));
            Alert::success('Server was successfully deleted from the system.')->flash();

            return redirect()->route('admin.servers');
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (TransferException $ex) {
            Log::warning($ex);
            Alert::danger('A TransferException occurred while attempting to delete this server from the daemon, please ensure it is running. This error has been logged.')->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to delete this server. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.delete', $id);
    }

    /**
     * Update the startup command as well as variables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveStartup(Request $request, $id)
    {
        $repo = new ServerRepository;

        try {
            if ($repo->updateStartup($id, $request->except('_token'), true)) {
                Alert::success('Service configuration successfully modfied for this server, reinstalling now.')->flash();

                return redirect()->route('admin.servers.view', $id);
            } else {
                Alert::success('Startup variables were successfully modified and assigned for this server.')->flash();
            }
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.view.startup', $id)->withErrors(json_decode($ex->getMessage()));
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (TransferException $ex) {
            Log::warning($ex);
            Alert::danger('A TransferException occurred while attempting to update the startup for this server, please ensure the daemon is running. This error has been logged.')->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An unhandled exception occured while attemping to update startup variables for this server. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.startup', $id);
    }

    /**
     * Creates a new database assigned to a specific server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function newDatabase(Request $request, $id)
    {
        $repo = new DatabaseRepository;

        try {
            $repo->create($id, $request->only(['host', 'database', 'connection']));

            Alert::success('A new database was assigned to this server successfully.')->flash();
        } catch (DisplayValidationException $ex) {
            return redirect()->route('admin.servers.view.database', $id)->withInput()->withErrors(json_decode($ex->getMessage()))->withInput();
        } catch (DisplayException $ex) {
            Alert::danger($ex->getMessage())->flash();
        } catch (\Exception $ex) {
            Log::error($ex);
            Alert::danger('An exception occured while attempting to add a new database for this server. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.view.database', $id)->withInput();
    }

    /**
     * Resets the database password for a specific database on this server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetDatabasePassword(Request $request, $id)
    {
        $database = Models\Database::where('server_id', $id)->findOrFail($request->input('database'));
        $repo = new DatabaseRepository;

        try {
            $repo->password($database->id, str_random(20));

            return response('', 204);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json(['error' => 'A unhandled exception occurred while attempting to reset this password. This error has been logged.'], 503);
        }
    }

    /**
     * Deletes a database from a server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @param  int                       $database
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteDatabase(Request $request, $id, $database)
    {
        $database = Models\Database::where('server_id', $id)->findOrFail($database);
        $repo = new DatabaseRepository;

        try {
            $repo->drop($database->id);

            return response('', 204);
        } catch (\Exception $ex) {
            Log::error($ex);

            return response()->json(['error' => 'A unhandled exception occurred while attempting to drop this database. This error has been logged.'], 503);
        }
    }
}
