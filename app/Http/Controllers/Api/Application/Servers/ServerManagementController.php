<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Services\Servers\ContainerRebuildService;
use Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class ServerManagementController extends ApplicationApiController
{
    /**
     * @var \Pterodactyl\Services\Servers\ContainerRebuildService
     */
    private $rebuildService;

    /**
     * @var \Pterodactyl\Services\Servers\ReinstallServerService
     */
    private $reinstallServerService;

    /**
     * @var \Pterodactyl\Services\Servers\SuspensionService
     */
    private $suspensionService;

    /**
     * SuspensionController constructor.
     *
     * @param \Pterodactyl\Services\Servers\ContainerRebuildService $rebuildService
     * @param \Pterodactyl\Services\Servers\ReinstallServerService  $reinstallServerService
     * @param \Pterodactyl\Services\Servers\SuspensionService       $suspensionService
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
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function suspend(ServerWriteRequest $request): Response
    {
        $this->suspensionService->toggle($request->getModel(Server::class), SuspensionService::ACTION_SUSPEND);

        return $this->returnNoContent();
    }

    /**
     * Unsuspend a server on the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function unsuspend(ServerWriteRequest $request): Response
    {
        $this->suspensionService->toggle($request->getModel(Server::class), SuspensionService::ACTION_UNSUSPEND);

        return $this->returnNoContent();
    }

    /**
     * Mark a server as needing to be reinstalled.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function reinstall(ServerWriteRequest $request): Response
    {
        $this->reinstallServerService->reinstall($request->getModel(Server::class));

        return $this->returnNoContent();
    }

    /**
     * Mark a server as needing its container rebuilt the next time it is started.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function rebuild(ServerWriteRequest $request): Response
    {
        $this->rebuildService->handle($request->getModel(Server::class));

        return $this->returnNoContent();
    }
}
