<?php

namespace App\Http\Controllers\Api\Application\Servers;

use App\Models\User;
use App\Models\Server;
use App\Services\Servers\StartupModificationService;
use App\Transformers\Api\Application\ServerTransformer;
use App\Http\Controllers\Api\Application\ApplicationApiController;
use App\Http\Requests\Api\Application\Servers\UpdateServerStartupRequest;

class StartupController extends ApplicationApiController
{
    /**
     * @var \App\Services\Servers\StartupModificationService
     */
    private $modificationService;

    /**
     * StartupController constructor.
     *
     * @param \App\Services\Servers\StartupModificationService $modificationService
     */
    public function __construct(StartupModificationService $modificationService)
    {
        parent::__construct();

        $this->modificationService = $modificationService;
    }

    /**
     * Update the startup and environment settings for a specific server.
     *
     * @param \App\Http\Requests\Api\Application\Servers\UpdateServerStartupRequest $request
     * @return array
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function index(UpdateServerStartupRequest $request): array
    {
        $server = $this->modificationService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle($request->getModel(Server::class), $request->validated());

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
