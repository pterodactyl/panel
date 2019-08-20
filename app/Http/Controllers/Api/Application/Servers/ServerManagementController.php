<?php

namespace App\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use App\Models\Server;
use App\Services\Servers\SuspensionService;
use App\Services\Servers\ReinstallServerService;
use App\Services\Servers\ContainerRebuildService;
use App\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use App\Http\Controllers\Api\Application\ApplicationApiController;

class ServerManagementController extends ApplicationApiController
{
    /**
     * @var \App\Services\Servers\ContainerRebuildService
     */
    private $rebuildService;

    /**
     * @var \App\Services\Servers\ReinstallServerService
     */
    private $reinstallServerService;

    /**
     * @var \App\Services\Servers\SuspensionService
     */
    private $suspensionService;

    /**
     * SuspensionController constructor.
     *
     * @param \App\Services\Servers\ContainerRebuildService $rebuildService
     * @param \App\Services\Servers\ReinstallServerService  $reinstallServerService
     * @param \App\Services\Servers\SuspensionService       $suspensionService
     */
    public function __construct(
        ContainerRebuildService $rebuildService,
        ReinstallServerService $reinstallServerService,
        SuspensionService $suspensionService
    ) {
        parent::__construct();

        $this->rebuildService = $rebuildService;
        $this->reinstallServerService = $reinstallServerService;
        $this->suspensionService = $suspensionService;
    }

    /**
     * Suspend a server on the Panel.
     *
     * @param \App\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function suspend(ServerWriteRequest $request): Response
    {
        $this->suspensionService->toggle($request->getModel(Server::class), SuspensionService::ACTION_SUSPEND);

        return $this->returnNoContent();
    }

    /**
     * Unsuspend a server on the Panel.
     *
     * @param \App\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function unsuspend(ServerWriteRequest $request): Response
    {
        $this->suspensionService->toggle($request->getModel(Server::class), SuspensionService::ACTION_UNSUSPEND);

        return $this->returnNoContent();
    }

    /**
     * Mark a server as needing to be reinstalled.
     *
     * @param \App\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function reinstall(ServerWriteRequest $request): Response
    {
        $this->reinstallServerService->reinstall($request->getModel(Server::class));

        return $this->returnNoContent();
    }

    /**
     * Mark a server as needing its container rebuilt the next time it is started.
     *
     * @param \App\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \App\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function rebuild(ServerWriteRequest $request): Response
    {
        $this->rebuildService->handle($request->getModel(Server::class));

        return $this->returnNoContent();
    }
}
