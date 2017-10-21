<?php

namespace Pterodactyl\Http\Controllers\Server;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Services\Databases\DatabasePasswordService;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabaseController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Pterodactyl\Services\Databases\DatabasePasswordService
     */
    protected $passwordService;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $repository;

    /**
     * DatabaseController constructor.
     *
     * @param \Pterodactyl\Services\Databases\DatabasePasswordService       $passwordService
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $repository
     */
    public function __construct(DatabasePasswordService $passwordService, DatabaseRepositoryInterface $repository)
    {
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

        return view('server.databases.index', [
            'databases' => $this->repository->getDatabasesForServer($server->id),
        ]);
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
}
