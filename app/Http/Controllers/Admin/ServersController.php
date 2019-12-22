<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Services\Servers\BuildModificationService;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Services\Servers\DetailsModificationService;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Repositories\Eloquent\DatabaseHostRepository;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Http\Requests\Admin\Servers\Databases\StoreServerDatabaseRequest;

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
     * @var \Pterodactyl\Contracts\Repository\NestRepositoryInterface
     */
    protected $nestRepository;

    /**
     * @var \Pterodactyl\Services\Servers\ReinstallServerService
     */
    protected $reinstallService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

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
     * @param \Prologue\Alerts\AlertsMessageBag $alert
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface $allocationRepository
     * @param \Pterodactyl\Services\Servers\BuildModificationService $buildModificationService
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService $databaseManagementService
     * @param \Pterodactyl\Services\Databases\DatabasePasswordService $databasePasswordService
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $databaseRepository
     * @param \Pterodactyl\Repositories\Eloquent\DatabaseHostRepository $databaseHostRepository
     * @param \Pterodactyl\Services\Servers\ServerDeletionService $deletionService
     * @param \Pterodactyl\Services\Servers\DetailsModificationService $detailsModificationService
     * @param \Pterodactyl\Services\Servers\ReinstallServerService $reinstallService
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     * @param \Pterodactyl\Contracts\Repository\NestRepositoryInterface $nestRepository
     * @param \Pterodactyl\Services\Servers\StartupModificationService $startupModificationService
     * @param \Pterodactyl\Services\Servers\SuspensionService $suspensionService
     */
    public function __construct(
        AlertsMessageBag $alert,
        AllocationRepositoryInterface $allocationRepository,
        BuildModificationService $buildModificationService,
        ConfigRepository $config,
        DatabaseManagementService $databaseManagementService,
        DatabasePasswordService $databasePasswordService,
        DatabaseRepositoryInterface $databaseRepository,
        DatabaseHostRepository $databaseHostRepository,
        ServerDeletionService $deletionService,
        DetailsModificationService $detailsModificationService,
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
        $this->databaseHostRepository = $databaseHostRepository;
        $this->databaseManagementService = $databaseManagementService;
        $this->databasePasswordService = $databasePasswordService;
        $this->databaseRepository = $databaseRepository;
        $this->detailsModificationService = $detailsModificationService;
        $this->deletionService = $deletionService;
        $this->nestRepository = $nestRepository;
        $this->reinstallService = $reinstallService;
        $this->repository = $repository;
        $this->startupModificationService = $startupModificationService;
        $this->suspensionService = $suspensionService;
    }

    /**
     * Update the details for a server.
     *
     * @param \Illuminate\Http\Request $request
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
     * Manage the suspension status for a server.
     *
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
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
            'database_limit', 'allocation_limit', 'oom_disabled',
        ]));
        $this->alert->success(trans('admin/server.alerts.build_updated'))->flash();

        return redirect()->route('admin.servers.view.build', $server->id);
    }

    /**
     * Start the server deletion process.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Throwable
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
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
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
     * @param \Pterodactyl\Http\Requests\Admin\Servers\Databases\StoreServerDatabaseRequest $request
     * @param int $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function newDatabase(StoreServerDatabaseRequest $request, $server)
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
     * @param int $server
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function resetDatabasePassword(Request $request, $server)
    {
        $database = $this->databaseRepository->findFirstWhere([
            ['server_id', '=', $server],
            ['id', '=', $request->input('database')],
        ]);

        $this->databasePasswordService->handle($database);

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
