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

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Log;
use Alert;
use Javascript;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\ServerFormRequest;
use Pterodactyl\Models;
use Pterodactyl\Models\Server;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\DatabaseHostRepository;
use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Services\Servers\CreationService;
use Pterodactyl\Services\Servers\DetailsModificationService;

class ServersController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $databaseRepository;

    /**
     * @var \Pterodactyl\Services\Database\CreationService
     */
    protected $databaseCreationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    protected $databaseHostRepository;

    /**
     * @var \Pterodactyl\Services\Servers\DetailsModificationService
     */
    protected $detailsModificationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Servers\CreationService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $serviceRepository;

    public function __construct(
        AlertsMessageBag $alert,
        AllocationRepositoryInterface $allocationRepository,
        ConfigRepository $config,
        CreationService $service,
        \Pterodactyl\Services\Database\CreationService $databaseCreationService,
        DatabaseRepositoryInterface $databaseRepository,
        DatabaseHostRepository $databaseHostRepository,
        DetailsModificationService $detailsModificationService,
        LocationRepositoryInterface $locationRepository,
        NodeRepositoryInterface $nodeRepository,
        ServerRepositoryInterface $repository,
        ServiceRepositoryInterface $serviceRepository
    ) {
        $this->alert = $alert;
        $this->allocationRepository = $allocationRepository;
        $this->config = $config;
        $this->databaseCreationService = $databaseCreationService;
        $this->databaseRepository = $databaseRepository;
        $this->databaseHostRepository = $databaseHostRepository;
        $this->detailsModificationService = $detailsModificationService;
        $this->locationRepository = $locationRepository;
        $this->nodeRepository = $nodeRepository;
        $this->repository = $repository;
        $this->service = $service;
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * Display the index page with all servers currently on the system.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.servers.index', [
            'servers' => $this->repository->getAllServers(
                $this->config->get('pterodactyl.paginate.admin.servers')
            ),
        ]);
    }

    /**
     * Display create new server page.
     *
     * @return \Illuminate\View\View
     *
     * @throws \Exception
     */
    public function create()
    {
        $services = $this->serviceRepository->getWithOptions();

        Javascript::put([
            'services' => $services->map(function ($item) {
                return array_merge($item->toArray(), [
                    'options' => $item->options->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
        ]);

        return view('admin.servers.new', [
            'locations' => $this->locationRepository->all(),
            'services' => $services,
        ]);
    }

    /**
     * Handle POST of server creation form.
     *
     * @param  \Pterodactyl\Http\Requests\Admin\ServerFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(ServerFormRequest $request)
    {
        try {
            $server = $this->service->create($request->except('_token'));

            return redirect()->route('admin.servers.view', $server->id);
        } catch (TransferException $ex) {
            Log::warning($ex);
            Alert::danger('A TransferException was encountered while trying to contact the daemon, please ensure it is online and accessible. This error has been logged.')->flash();
        }

        return redirect()->route('admin.servers.new')->withInput();
    }

    /**
     * Returns a tree of all avaliable nodes in a given location.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Collection
     */
    public function nodes(Request $request)
    {
        return $this->nodeRepository->getNodesForLocation($request->input('location'));
    }

    /**
     * Display the index when viewing a specific server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\View\View
     */
    public function viewIndex(Request $request, $id)
    {
        return view('admin.servers.view.index', ['server' => $this->repository->find($id)]);
    }

    /**
     * Display the details page when viewing a specific server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\View\View
     */
    public function viewDetails(Request $request, $id)
    {
        return view('admin.servers.view.details', [
            'server' => $this->repository->findFirstWhere([
                ['id', '=', $id],
                ['installed', '=', 1],
            ]),
        ]);
    }

    /**
     * Display the build details page when viewing a specific server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\View\View
     */
    public function viewBuild(Request $request, $id)
    {
        $server = $this->repository->findFirstWhere([
            ['id', '=', $id],
            ['installed', '=', 1],
        ]);

        $allocations = $this->allocationRepository->getAllocationsForNode($server->node_id);

        return view('admin.servers.view.build', [
            'server' => $server,
            'assigned' => $allocations->where('server_id', $server->id)->sortBy('port')->sortBy('ip'),
            'unassigned' => $allocations->where('server_id', null)->sortBy('port')->sortBy('ip'),
        ]);
    }

    /**
     * Display startup configuration page for a server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\View\View
     */
    public function viewStartup(Request $request, $id)
    {
        $parameters = $this->repository->getVariablesWithValues($id, true);
        if (! $parameters->server->installed) {
            abort(404);
        }

        $services = $this->serviceRepository->getWithOptions();

        Javascript::put([
            'services' => $services->map(function ($item) {
                return array_merge($item->toArray(), [
                    'options' => $item->options->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
            'server_variables' => $parameters->data,
        ]);

        return view('admin.servers.view.startup', [
            'server' => $parameters->server,
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
        $server = $this->repository->getWithDatabases($id);

        return view('admin.servers.view.database', [
            'hosts' => $this->databaseHostRepository->all(),
            'server' => $server,
        ]);
    }

    /**
     * Display the management page when viewing a specific server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\View\View
     */
    public function viewManage(Request $request, $id)
    {
        return view('admin.servers.view.manage', ['server' => $this->repository->find($id)]);
    }

    /**
     * Display the deletion page for a server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\View\View
     */
    public function viewDelete(Request $request, $id)
    {
        return view('admin.servers.view.delete', ['server' => $this->repository->find($id)]);
    }

    /**
     * Update the details for a server.
     *
     * @param \Illuminate\Http\Request   $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function setDetails(Request $request, Server $server)
    {
        $this->detailsModificationService->edit($server, $request->only([
            'owner_id', 'name', 'description', 'reset_token',
        ]));

        $this->alert->success(trans('admin/server.alerts.details_updated'))->flash();

        return redirect()->route('admin.servers.view.details', $server->id);
    }

    /**
     * Set the new docker container for a server.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function setContainer(Request $request, Server $server)
    {
        $this->detailsModificationService->setDockerImage($server, $request->input('docker_image'));
        $this->alert->success(trans('admin/server.alerts.docker_image_updated'))->flash();

        return redirect()->route('admin.servers.view.details', $server->id);
    }

    /**
     * Toggles the install status for a server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
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
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
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
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
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
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
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
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
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
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
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
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
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
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function newDatabase(Request $request, $id)
    {
        $this->databaseCreationService->create($id, [
            'database' => $request->input('database'),
            'remote' => $request->input('remote'),
            'database_host_id' => $request->input('database_host_id'),
        ]);

        return redirect()->route('admin.servers.view.database', $id)->withInput();
    }

    /**
     * Resets the database password for a specific database on this server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function resetDatabasePassword(Request $request, $id)
    {
        $database = $this->databaseRepository->findFirstWhere([
            ['server_id', '=', $id],
            ['id', '=', $request->input('database')],
        ]);

        $this->databaseCreationService->changePassword($database->id, str_random(20));

        return response('', 204);
    }

    /**
     * Deletes a database from a server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @param  int                      $database
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function deleteDatabase(Request $request, $id, $database)
    {
        $database = $this->databaseRepository->findFirstWhere([
            ['server_id', '=', $id],
            ['id', '=', $database],
        ]);

        $this->databaseCreationService->delete($database->id);

        return response('', 204);
    }
}
