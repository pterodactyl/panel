<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Javascript;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Http\Requests\Admin\ServerFormRequest;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Services\Servers\ContainerRebuildService;
use Pterodactyl\Services\Servers\BuildModificationService;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Services\Servers\DetailsModificationService;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Repositories\Eloquent\DatabaseHostRepository;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

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
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    protected $databaseManagementService;

    /**
     * @var \Pterodactyl\Services\Databases\DatabasePasswordService
     */
    protected $databasePasswordService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    protected $databaseHostRepository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerDeletionService
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
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $nestRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * @var \Pterodactyl\Services\Servers\ReinstallServerService
     */
    protected $reinstallService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerCreationService
     */
    protected $service;

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
     * @param \Pterodactyl\Services\Servers\ServerCreationService             $service
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService       $databaseManagementService
     * @param \Pterodactyl\Services\Databases\DatabasePasswordService         $databasePasswordService
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface   $databaseRepository
     * @param \Pterodactyl\Repositories\Eloquent\DatabaseHostRepository       $databaseHostRepository
     * @param \Pterodactyl\Services\Servers\ServerDeletionService             $deletionService
     * @param \Pterodactyl\Services\Servers\DetailsModificationService        $detailsModificationService
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface   $locationRepository
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface       $nodeRepository
     * @param \Pterodactyl\Services\Servers\ReinstallServerService            $reinstallService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface     $repository
     * @param \Pterodactyl\Contracts\Repository\NestRepositoryInterface       $nestRepository
     * @param \Pterodactyl\Services\Servers\StartupModificationService        $startupModificationService
     * @param \Pterodactyl\Services\Servers\SuspensionService                 $suspensionService
     */
    public function __construct(
        AlertsMessageBag $alert,
        AllocationRepositoryInterface $allocationRepository,
        BuildModificationService $buildModificationService,
        ConfigRepository $config,
        ContainerRebuildService $containerRebuildService,
        ServerCreationService $service,
        DatabaseManagementService $databaseManagementService,
        DatabasePasswordService $databasePasswordService,
        DatabaseRepositoryInterface $databaseRepository,
        DatabaseHostRepository $databaseHostRepository,
        ServerDeletionService $deletionService,
        DetailsModificationService $detailsModificationService,
        LocationRepositoryInterface $locationRepository,
        NodeRepositoryInterface $nodeRepository,
        ReinstallServerService $reinstallService,
        ServerRepositoryInterface $repository,
        NestRepositoryInterface $nestRepository,
        StartupModificationService $startupModificationService,
        SuspensionService $suspensionService
    ) {
        $this->alert = $alert;
        $this->allocationRepository = $allocationRepository;
        $this->buildModificationService = $buildModificationService;
        $this->config = $config;
        $this->containerRebuildService = $containerRebuildService;
        $this->databaseHostRepository = $databaseHostRepository;
        $this->databaseManagementService = $databaseManagementService;
        $this->databasePasswordService = $databasePasswordService;
        $this->databaseRepository = $databaseRepository;
        $this->detailsModificationService = $detailsModificationService;
        $this->deletionService = $deletionService;
        $this->locationRepository = $locationRepository;
        $this->nestRepository = $nestRepository;
        $this->nodeRepository = $nodeRepository;
        $this->reinstallService = $reinstallService;
        $this->repository = $repository;
        $this->service = $service;
        $this->startupModificationService = $startupModificationService;
        $this->suspensionService = $suspensionService;
    }

    /**
     * Display the index page with all servers currently on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('admin.servers.index', [
            'servers' => $this->repository->setSearchTerm($request->input('query'))->getAllServers(
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
        $nodes = $this->nodeRepository->all();
        if (count($nodes) < 1) {
            $this->alert->warning(trans('admin/server.alerts.node_required'))->flash();

            return redirect()->route('admin.nodes');
        }

        $nests = $this->nestRepository->getWithEggs();

        Javascript::put([
            'nodeData' => $this->nodeRepository->getNodesForServerCreation(),
            'nests' => $nests->map(function ($item) {
                return array_merge($item->toArray(), [
                    'eggs' => $item->eggs->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
        ]);

        return view('admin.servers.new', [
            'locations' => $this->locationRepository->all(),
            'nests' => $nests,
        ]);
    }

    /**
     * Handle POST of server creation form.
     *
     * @param \Pterodactyl\Http\Requests\Admin\ServerFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     */
    public function store(ServerFormRequest $request)
    {
        $server = $this->service->handle($request->except('_token'));
        $this->alert->success(trans('admin/server.alerts.server_created'))->flash();

        return redirect()->route('admin.servers.view', $server->id);
    }

    /**
     * Display the index when viewing a specific server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\View\View
     */
    public function viewIndex(Server $server)
    {
        return view('admin.servers.view.index', ['server' => $server]);
    }

    /**
     * Display the details page when viewing a specific server.
     *
     * @param int $server
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
     * @param int $server
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function viewStartup(Server $server)
    {
        $parameters = $this->repository->getVariablesWithValues($server->id, true);
        if (! $parameters->server->installed) {
            abort(404);
        }

        $nests = $this->nestRepository->getWithEggs();

        Javascript::put([
            'server' => $server,
            'nests' => $nests->map(function ($item) {
                return array_merge($item->toArray(), [
                    'eggs' => $item->eggs->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
            'server_variables' => $parameters->data,
        ]);

        return view('admin.servers.view.startup', [
            'server' => $parameters->server,
            'nests' => $nests,
        ]);
    }

    /**
     * Display the database management page for a specific server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\View\View
     */
    public function viewDatabase(Server $server)
    {
        $this->repository->loadDatabaseRelations($server);

        return view('admin.servers.view.database', [
            'hosts' => $this->databaseHostRepository->all(),
            'server' => $server,
        ]);
    }

    /**
     * Display the management page when viewing a specific server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\View\View
     */
    public function viewManage(Server $server)
    {
        return view('admin.servers.view.manage', ['server' => $server]);
    }

    /**
     * Display the deletion page for a server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\View\View
     */
    public function viewDelete(Server $server)
    {
        return view('admin.servers.view.delete', ['server' => $server]);
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
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function setDetails(Request $request, Server $server)
    {
        $this->detailsModificationService->handle($server, $request->only([
            'owner_id', 'external_id', 'name', 'description',
        ]));

        $this->alert->success(trans('admin/server.alerts.details_updated'))->flash();

        return redirect()->route('admin.servers.view.details', $server->id);
    }

    /**
     * Toggles the install status for a server.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function toggleInstall(Server $server)
    {
        if ($server->installed > 1) {
            throw new DisplayException(trans('admin/server.exceptions.marked_as_failed'));
        }

        $this->repository->update($server->id, [
            'installed' => ! $server->installed,
        ], true, true);

        $this->alert->success(trans('admin/server.alerts.install_toggled'))->flash();

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Reinstalls the server with the currently assigned pack and service.
     *
     * @param \Pterodactyl\Models\Server $server
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
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rebuildContainer(Server $server)
    {
        $this->containerRebuildService->handle($server);
        $this->alert->success(trans('admin/server.alerts.rebuild_on_boot'))->flash();

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Manage the suspension status for a server.
     *
     * @param \Illuminate\Http\Request   $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
     * @param \Illuminate\Http\Request   $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function updateBuild(Request $request, Server $server)
    {
        $this->buildModificationService->handle($server, $request->only([
            'allocation_id', 'add_allocations', 'remove_allocations',
            'memory', 'swap', 'io', 'cpu', 'disk',
            'database_limit', 'allocation_limit',
        ]));
        $this->alert->success(trans('admin/server.alerts.build_updated'))->flash();

        return redirect()->route('admin.servers.view.build', $server->id);
    }

    /**
     * Start the server deletion process.
     *
     * @param \Illuminate\Http\Request   $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(Request $request, Server $server)
    {
        $this->deletionService->withForce($request->filled('force_delete'))->handle($server);
        $this->alert->success(trans('admin/server.alerts.server_deleted'))->flash();

        return redirect()->route('admin.servers');
    }

    /**
     * Update the startup command as well as variables.
     *
     * @param \Illuminate\Http\Request   $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function saveStartup(Request $request, Server $server)
    {
        $this->startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN);
        $this->startupModificationService->handle($server, $request->except('_token'));
        $this->alert->success(trans('admin/server.alerts.startup_changed'))->flash();

        return redirect()->route('admin.servers.view.startup', $server->id);
    }

    /**
     * Creates a new database assigned to a specific server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $server
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
     * @param \Illuminate\Http\Request $request
     * @param int                      $server
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

        $this->databasePasswordService->handle($database, str_random(20));

        return response('', 204);
    }

    /**
     * Deletes a database from a server.
     *
     * @param int $server
     * @param int $database
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
