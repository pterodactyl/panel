<?php

namespace Pterodactyl\Http\Controllers\Server;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Services\Databases\DatabaseManagementService;
use Pterodactyl\Services\Databases\DeployServerDatabaseService;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;
use Pterodactyl\Http\Requests\Server\Database\StoreServerDatabaseRequest;
use Pterodactyl\Http\Requests\Server\Database\DeleteServerDatabaseRequest;

class DatabaseController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Pterodactyl\Services\Databases\DeployServerDatabaseService
     */
    private $deployServerDatabaseService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $databaseHostRepository;

    /**
     * @var \Pterodactyl\Services\Databases\DatabaseManagementService
     */
    private $managementService;

    /**
     * @var \Pterodactyl\Services\Databases\DatabasePasswordService
     */
    private $passwordService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * DatabaseController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                                 $alert
     * @param \Pterodactyl\Services\Databases\DeployServerDatabaseService       $deployServerDatabaseService
     * @param \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface $databaseHostRepository
     * @param \Pterodactyl\Services\Databases\DatabaseManagementService         $managementService
     * @param \Pterodactyl\Services\Databases\DatabasePasswordService           $passwordService
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface     $repository
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
     * @param \Pterodactyl\Http\Requests\Server\Database\StoreServerDatabaseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
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
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorize('reset-db-password', $request->attributes->get('server'));

        $password = str_random(20);
        $this->passwordService->handle($request->attributes->get('database'), $password);

        return response()->json(['password' => $password]);
    }

    /**
     * Delete a database for this server from the SQL server and Panel database.
     *
     * @param \Pterodactyl\Http\Requests\Server\Database\DeleteServerDatabaseRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(DeleteServerDatabaseRequest $request): Response
    {
        $this->managementService->delete($request->attributes->get('database')->id);

        return response('', Response::HTTP_NO_CONTENT);
    }
}
