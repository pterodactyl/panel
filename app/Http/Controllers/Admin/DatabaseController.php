<?php

namespace Pterodactyl\Http\Controllers\Admin;

use PDOException;
use Illuminate\View\View;
use Pterodactyl\Models\DatabaseHost;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Databases\Hosts\HostUpdateService;
use Pterodactyl\Http\Requests\Admin\DatabaseHostFormRequest;
use Pterodactyl\Services\Databases\Hosts\HostCreationService;
use Pterodactyl\Services\Databases\Hosts\HostDeletionService;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class DatabaseController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostCreationService
     */
    private $creationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $databaseRepository;

    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostDeletionService
     */
    private $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostUpdateService
     */
    private $updateService;

    /**
     * DatabaseController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                                 $alert
     * @param \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface $repository
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface     $databaseRepository
     * @param \Pterodactyl\Services\Databases\Hosts\HostCreationService         $creationService
     * @param \Pterodactyl\Services\Databases\Hosts\HostDeletionService         $deletionService
     * @param \Pterodactyl\Services\Databases\Hosts\HostUpdateService           $updateService
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface     $locationRepository
     */
    public function __construct(
        AlertsMessageBag $alert,
        DatabaseHostRepositoryInterface $repository,
        DatabaseRepositoryInterface $databaseRepository,
        HostCreationService $creationService,
        HostDeletionService $deletionService,
        HostUpdateService $updateService,
        LocationRepositoryInterface $locationRepository
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->databaseRepository = $databaseRepository;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->locationRepository = $locationRepository;
        $this->updateService = $updateService;
    }

    /**
     * Display database host index.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        return view('admin.databases.index', [
            'locations' => $this->locationRepository->getAllWithNodes(),
            'hosts' => $this->repository->getWithViewDetails(),
        ]);
    }

    /**
     * Display database host to user.
     *
     * @param int $host
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function view(int $host): View
    {
        return view('admin.databases.view', [
            'locations' => $this->locationRepository->getAllWithNodes(),
            'host' => $this->repository->find($host),
            'databases' => $this->databaseRepository->getDatabasesForHost($host),
        ]);
    }

    /**
     * Handle request to create a new database host.
     *
     * @param \Pterodactyl\Http\Requests\Admin\DatabaseHostFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function create(DatabaseHostFormRequest $request): RedirectResponse
    {
        try {
            $host = $this->creationService->handle($request->normalize());
        } catch (PDOException $ex) {
            $this->alert->danger($ex->getMessage())->flash();

            return redirect()->route('admin.databases');
        }

        $this->alert->success('Successfully created a new database host on the system.')->flash();

        return redirect()->route('admin.databases.view', $host->id);
    }

    /**
     * Handle updating database host.
     *
     * @param \Pterodactyl\Http\Requests\Admin\DatabaseHostFormRequest $request
     * @param \Pterodactyl\Models\DatabaseHost                         $host
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(DatabaseHostFormRequest $request, DatabaseHost $host): RedirectResponse
    {
        $redirect = redirect()->route('admin.databases.view', $host->id);

        try {
            $this->updateService->handle($host->id, $request->normalize());
            $this->alert->success('Database host was updated successfully.')->flash();
        } catch (PDOException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
            $redirect->withInput($request->normalize());
        }

        return $redirect;
    }

    /**
     * Handle request to delete a database host.
     *
     * @param int $host
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function delete(int $host): RedirectResponse
    {
        $this->deletionService->handle($host);
        $this->alert->success('The requested database host has been deleted from the system.')->flash();

        return redirect()->route('admin.databases');
    }
}
