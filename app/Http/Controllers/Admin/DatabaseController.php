<?php

namespace App\Http\Controllers\Admin;

use Exception;
use PDOException;
use Illuminate\View\View;
use App\Models\DatabaseHost;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use App\Services\Databases\Hosts\HostUpdateService;
use App\Http\Requests\Admin\DatabaseHostFormRequest;
use App\Services\Databases\Hosts\HostCreationService;
use App\Services\Databases\Hosts\HostDeletionService;
use App\Contracts\Repository\DatabaseRepositoryInterface;
use App\Contracts\Repository\LocationRepositoryInterface;
use App\Contracts\Repository\DatabaseHostRepositoryInterface;

class DatabaseController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \App\Services\Databases\Hosts\HostCreationService
     */
    private $creationService;

    /**
     * @var \App\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $databaseRepository;

    /**
     * @var \App\Services\Databases\Hosts\HostDeletionService
     */
    private $deletionService;

    /**
     * @var \App\Contracts\Repository\LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var \App\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $repository;

    /**
     * @var \App\Services\Databases\Hosts\HostUpdateService
     */
    private $updateService;

    /**
     * DatabaseController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                                 $alert
     * @param \App\Contracts\Repository\DatabaseHostRepositoryInterface $repository
     * @param \App\Contracts\Repository\DatabaseRepositoryInterface     $databaseRepository
     * @param \App\Services\Databases\Hosts\HostCreationService         $creationService
     * @param \App\Services\Databases\Hosts\HostDeletionService         $deletionService
     * @param \App\Services\Databases\Hosts\HostUpdateService           $updateService
     * @param \App\Contracts\Repository\LocationRepositoryInterface     $locationRepository
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
     * @throws \App\Exceptions\Repository\RecordNotFoundException
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
     * @param \App\Http\Requests\Admin\DatabaseHostFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function create(DatabaseHostFormRequest $request): RedirectResponse
    {
        try {
            $host = $this->creationService->handle($request->normalize());
        } catch (Exception $exception) {
            if ($exception instanceof PDOException || $exception->getPrevious() instanceof PDOException) {
                $this->alert->danger(
                    sprintf('There was an error while trying to connect to the host or while executing a query: "%s"', $exception->getMessage())
                )->flash();

                redirect()->route('admin.databases')->withInput($request->validated());
            } else {
                throw $exception;
            }
        }

        $this->alert->success('Successfully created a new database host on the system.')->flash();

        return redirect()->route('admin.databases.view', $host->id);
    }

    /**
     * Handle updating database host.
     *
     * @param \App\Http\Requests\Admin\DatabaseHostFormRequest $request
     * @param \App\Models\DatabaseHost                         $host
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function update(DatabaseHostFormRequest $request, DatabaseHost $host): RedirectResponse
    {
        $redirect = redirect()->route('admin.databases.view', $host->id);

        try {
            $this->updateService->handle($host->id, $request->normalize());
            $this->alert->success('Database host was updated successfully.')->flash();
        } catch (Exception $exception) {
            // Catch any SQL related exceptions and display them back to the user, otherwise just
            // throw the exception like normal and move on with it.
            if ($exception instanceof PDOException || $exception->getPrevious() instanceof PDOException) {
                $this->alert->danger(
                    sprintf('There was an error while trying to connect to the host or while executing a query: "%s"', $exception->getMessage())
                )->flash();
                $redirect->withInput($request->normalize());
            } else {
                throw $exception;
            }
        }

        return $redirect;
    }

    /**
     * Handle request to delete a database host.
     *
     * @param int $host
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\Service\HasActiveServersException
     */
    public function delete(int $host): RedirectResponse
    {
        $this->deletionService->handle($host);
        $this->alert->success('The requested database host has been deleted from the system.')->flash();

        return redirect()->route('admin.databases');
    }
}
