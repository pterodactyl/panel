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
use Pterodactyl\Models\Server;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Eloquent\DatabaseHostRepository;
use Pterodactyl\Services\Database\DatabaseManagementService;
use Pterodactyl\Services\Servers\BuildModificationService;
use Pterodactyl\Services\Servers\ContainerRebuildService;
use Pterodactyl\Services\Servers\CreationService;
use Pterodactyl\Services\Servers\DeletionService;
use Pterodactyl\Services\Servers\DetailsModificationService;
use Pterodactyl\Services\Servers\ReinstallService;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Services\Servers\SuspensionService;

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
     * @var \Pterodactyl\Services\Servers\BuildModificationService
     */
    protected $buildModificationService;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Services\Servers\ContainerRebuildService
     */
    protected $containerRebuildService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $databaseRepository;

    /**
     * @var \Pterodactyl\Services\Database\DatabaseManagementService
     */
    protected $databaseManagementService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    protected $databaseHostRepository;

    /**
     * @var \Pterodactyl\Services\Servers\DeletionService
     */
    protected $deletionService;

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
     * @var \Pterodactyl\Services\Servers\ReinstallService
     */
    protected $reinstallService;

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

    /**
     * @var \Pterodactyl\Services\Servers\StartupModificationService
     */
    private $startupModificationService;

    /**
     * @var \Pterodactyl\Services\Servers\SuspensionService
     */
    protected $suspensionService;

    /**
     * ServersController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                               $alert
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface $allocationRepository
     * @param \Pterodactyl\Services\Servers\BuildModificationService          $buildModificationService
     * @param \Illuminate\Contracts\Config\Repository                         $config
     * @param \Pterodactyl\Services\Servers\ContainerRebuildService           $containerRebuildService
     * @param \Pterodactyl\Services\Servers\CreationService                   $service
     * @param \Pterodactyl\Services\Database\DatabaseManagementService        $databaseManagementService
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface   $databaseRepository
     * @param \Pterodactyl\Repositories\Eloquent\DatabaseHostRepository       $databaseHostRepository
     * @param \Pterodactyl\Services\Servers\DeletionService                   $deletionService
     * @param \Pterodactyl\Services\Servers\DetailsModificationService        $detailsModificationService
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface   $locationRepository
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface       $nodeRepository
     * @param \Pterodactyl\Services\Servers\ReinstallService                  $reinstallService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface     $repository
     * @param \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface    $serviceRepository
     * @param \Pterodactyl\Services\Servers\StartupModificationService        $startupModificationService
     * @param \Pterodactyl\Services\Servers\SuspensionService                 $suspensionService
     */
    public function __construct(
        AlertsMessageBag $alert,
        AllocationRepositoryInterface $allocationRepository,
        BuildModificationService $buildModificationService,
        ConfigRepository $config,
        ContainerRebuildService $containerRebuildService,
        CreationService $service,
        DatabaseManagementService $databaseManagementService,
        DatabaseRepositoryInterface $databaseRepository,
        DatabaseHostRepository $databaseHostRepository,
        DeletionService $deletionService,
        DetailsModificationService $detailsModificationService,
        LocationRepositoryInterface $locationRepository,
        NodeRepositoryInterface $nodeRepository,
        ReinstallService $reinstallService,
        ServerRepositoryInterface $repository,
        ServiceRepositoryInterface $serviceRepository,
        StartupModificationService $startupModificationService,
        SuspensionService $suspensionService
    ) {
        $this->alert = $alert;
        $this->allocationRepository = $allocationRepository;
        $this->buildModificationService = $buildModificationService;
        $this->config = $config;
        $this->containerRebuildService = $containerRebuildService;
        $this->databaseManagementService = $databaseManagementService;
        $this->databaseRepository = $databaseRepository;
        $this->databaseHostRepository = $databaseHostRepository;
        $this->detailsModificationService = $detailsModificationService;
        $this->deletionService = $deletionService;
        $this->locationRepository = $locationRepository;
        $this->nodeRepository = $nodeRepository;
        $this->reinstallService = $reinstallService;
        $this->repository = $repository;
        $this->service = $service;
        $this->serviceRepository = $serviceRepository;
        $this->startupModificationService = $startupModificationService;
        $this->suspensionService = $suspensionService;
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
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function store(ServerFormRequest $request)
    {
        $server = $this->service->create($request->except('_token'));
        $this->alert->success(trans('admin/server.alerts.server_created'))->flash();

        return redirect()->route('admin.servers.view', $server->id);
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
     * @param  \Pterodactyl\Models\Server $server
     * @return \Illuminate\View\View
     */
    public function viewIndex(Server $server)
    {
        return view('admin.servers.view.index', ['server' => $server]);
    }

    /**
     * Display the details page when viewing a specific server.
     *
     * @param  int $server
     * @return \Illuminate\View\View
     */
    public function viewDetails($server)
    {
        return view('admin.servers.view.details', [
            'server' => $this->repository->findFirstWhere([
                ['id', '=', $server],
                ['installed', '=', 1],
            ]),
        ]);
    }

    /**
     * Display the build details page when viewing a specific server.
     *
     * @param  int $server
     * @return \Illuminate\View\View
     */
    public function viewBuild($server)
    {
        $server = $this->repository->findFirstWhere([
            ['id', '=', $server],
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
     * @param  int $server
     * @return \Illuminate\View\View
     */
    public function viewStartup($server)
    {
        $parameters = $this->repository->getVariablesWithValues($server, true);
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
     * @param  int $server
     * @return \Illuminate\View\View
     */
    public function viewDatabase($server)
    {
        $server = $this->repository->getWithDatabases($server);

        return view('admin.servers.view.database', [
            'hosts' => $this->databaseHostRepository->all(),
            'server' => $server,
        ]);
    }

    /**
     * Display the management page when viewing a specific server.
     *
     * @param  \Pterodactyl\Models\Server $server
     * @return \Illuminate\View\View
     */
    public function viewManage(Server $server)
    {
        return view('admin.servers.view.manage', ['server' => $server]);
    }

    /**
     * Display the deletion page for a server.
     *
     * @param  \Pterodactyl\Models\Server $server
     * @return \Illuminate\View\View
     */
    public function viewDelete(Server $server)
    {
        return view('admin.servers.view.delete', ['server' => $server]);
    }

    /**
     * Update the details for a server.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \Pterodactyl\Models\Server $server
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
     * @param  \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function toggleInstall(Server $server)
    {
        if ($server->installed > 1) {
            throw new DisplayException(trans('admin/server.exceptions.marked_as_failed'));
        }

        $this->repository->update($server->id, [
            'installed' => ! $server->installed,
        ]);

        $this->alert->success(trans('admin/server.alerts.install_toggled'))->flash();

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Reinstalls the server with the currently assigned pack and service.
     *
     * @param  \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function reinstallServer(Server $server)
    {
        $this->reinstallService->reinstall($server);
        $this->alert->success(trans('admin/server.alerts.server_reinstalled'))->flash();

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Setup a server to have a container rebuild.
     *
     * @param  \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function rebuildContainer(Server $server)
    {
        $this->containerRebuildService->rebuild($server);
        $this->alert->success(trans('admin/server.alerts.rebuild_on_boot'))->flash();

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Manage the suspension status for a server.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function manageSuspension(Request $request, Server $server)
    {
        $this->suspensionService->toggle($server, $request->input('action'));
        $this->alert->success(trans('admin/server.alerts.suspension_toggled', [
            'status' => $request->input('action') . 'ed',
        ]))->flash();

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Update the build configuration for a server.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @internal param int $id
     */
    public function updateBuild(Request $request, Server $server)
    {
        $this->buildModificationService->handle($server, $request->only([
            'allocation_id', 'add_allocations', 'remove_allocations',
            'memory', 'swap', 'io', 'cpu', 'disk',
        ]));
        $this->alert->success(trans('admin/server.alerts.build_updated'))->flash();

        return redirect()->route('admin.servers.view.build', $server->id);
    }

    /**
     * Start the server deletion process.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(Request $request, Server $server)
    {
        $this->deletionService->withForce($request->has('force_delete'))->handle($server);
        $this->alert->success(trans('admin/server.alerts.server_deleted'))->flash();

        return redirect()->route('admin.servers');
    }

    /**
     * Update the startup command as well as variables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function saveStartup(Request $request, Server $server)
    {
        $this->startupModificationService->isAdmin()->handle(
            $server, $request->except('_token')
        );
        $this->alert->success(trans('admin/server.alerts.startup_changed'))->flash();

        return redirect()->route('admin.servers.view.startup', $server->id);
    }

    /**
     * Creates a new database assigned to a specific server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function newDatabase(Request $request, $server)
    {
        $this->databaseManagementService->create($server, [
            'database' => $request->input('database'),
            'remote' => $request->input('remote'),
            'database_host_id' => $request->input('database_host_id'),
        ]);

        return redirect()->route('admin.servers.view.database', $server)->withInput();
    }

    /**
     * Resets the database password for a specific database on this server.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function resetDatabasePassword(Request $request, $server)
    {
        $database = $this->databaseRepository->findFirstWhere([
            ['server_id', '=', $server],
            ['id', '=', $request->input('database')],
        ]);

        $this->databaseManagementService->changePassword($database->id, str_random(20));

        return response('', 204);
    }

    /**
     * Deletes a database from a server.
     *
     * @param  int $server
     * @param  int $database
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function deleteDatabase($server, $database)
    {
        $database = $this->databaseRepository->findFirstWhere([
            ['server_id', '=', $server],
            ['id', '=', $database],
        ]);

        $this->databaseManagementService->delete($database->id);

        return response('', 204);
    }
}
