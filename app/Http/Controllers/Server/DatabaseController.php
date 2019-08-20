<?php

namespace App\Http\Controllers\Server;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use App\Http\Controllers\Controller;
use App\Traits\Controllers\JavascriptInjection;
use App\Services\Databases\DatabasePasswordService;
use App\Services\Databases\DatabaseManagementService;
use App\Services\Databases\DeployServerDatabaseService;
use App\Contracts\Repository\DatabaseRepositoryInterface;
use App\Contracts\Repository\DatabaseHostRepositoryInterface;
use App\Http\Requests\Server\Database\StoreServerDatabaseRequest;
use App\Http\Requests\Server\Database\DeleteServerDatabaseRequest;

class DatabaseController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \App\Services\Databases\DeployServerDatabaseService
     */
    private $deployServerDatabaseService;

    /**
     * @var \App\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $databaseHostRepository;

    /**
     * @var \App\Services\Databases\DatabaseManagementService
     */
    private $managementService;

    /**
     * @var \App\Services\Databases\DatabasePasswordService
     */
    private $passwordService;

    /**
     * @var \App\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * DatabaseController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                                 $alert
     * @param \App\Services\Databases\DeployServerDatabaseService       $deployServerDatabaseService
     * @param \App\Contracts\Repository\DatabaseHostRepositoryInterface $databaseHostRepository
     * @param \App\Services\Databases\DatabaseManagementService         $managementService
     * @param \App\Services\Databases\DatabasePasswordService           $passwordService
     * @param \App\Contracts\Repository\DatabaseRepositoryInterface     $repository
     */
    public function __construct(
        AlertsMessageBag $alert,
        DeployServerDatabaseService $deployServerDatabaseService,
        DatabaseHostRepositoryInterface $databaseHostRepository,
        DatabaseManagementService $managementService,
        DatabasePasswordService $passwordService,
        DatabaseRepositoryInterface $repository
    ) {
        $this->alert = $alert;
        $this->databaseHostRepository = $databaseHostRepository;
        $this->deployServerDatabaseService = $deployServerDatabaseService;
        $this->managementService = $managementService;
        $this->passwordService = $passwordService;
        $this->repository = $repository;
    }

    /**
     * Render the database listing for a server.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('view-databases', $server);
        $this->setRequest($request)->injectJavascript();

        $canCreateDatabase = config('pterodactyl.client_features.databases.enabled');
        $allowRandom = config('pterodactyl.client_features.databases.allow_random');

        if ($this->databaseHostRepository->findCountWhere([['node_id', '=', $server->node_id]]) === 0) {
            if ($canCreateDatabase && ! $allowRandom) {
                $canCreateDatabase = false;
            }
        }

        $databases = $this->repository->getDatabasesForServer($server->id);

        return view('server.databases.index', [
            'allowCreation' => $canCreateDatabase,
            'overLimit' => ! is_null($server->database_limit) && count($databases) >= $server->database_limit,
            'databases' => $databases,
        ]);
    }

    /**
     * Handle a request from a user to create a new database for the server.
     *
     * @param \App\Http\Requests\Server\Database\StoreServerDatabaseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \App\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function store(StoreServerDatabaseRequest $request): RedirectResponse
    {
        $this->deployServerDatabaseService->handle($request->getServer(), $request->validated());

        $this->alert->success('Successfully created a new database.')->flash();

        return redirect()->route('server.databases.index', $request->getServer()->uuidShort);
    }

    /**
     * Handle a request to update the password for a specific database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorize('reset-db-password', $request->attributes->get('server'));

        $password = $this->passwordService->handle($request->attributes->get('database'));

        return response()->json(['password' => $password]);
    }

    /**
     * Delete a database for this server from the SQL server and Panel database.
     *
     * @param \App\Http\Requests\Server\Database\DeleteServerDatabaseRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(DeleteServerDatabaseRequest $request): Response
    {
        $this->managementService->delete($request->attributes->get('database')->id);

        return response('', Response::HTTP_NO_CONTENT);
    }
}
