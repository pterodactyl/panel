<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Services\Servers\ReinstallServerService;
use Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class ServerManagementController extends ApplicationApiController
{
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
     * @param \Pterodactyl\Services\Servers\ReinstallServerService $reinstallServerService
     * @param \Pterodactyl\Services\Servers\SuspensionService $suspensionService
     */
    public function __construct(
        ReinstallServerService $reinstallServerService,
        SuspensionService $suspensionService
    ) {
        parent::__construct();

        $this->reinstallServerService = $reinstallServerService;
        $this->suspensionService = $suspensionService;
    }

    /**
     * Suspend a server on the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function suspend(ServerWriteRequest $request, Server $server): Response
    {
        $this->suspensionService->toggle($server, SuspensionService::ACTION_SUSPEND);

        return $this->returnNoContent();
    }

    /**
     * Unsuspend a server on the Panel.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function unsuspend(ServerWriteRequest $request, Server $server): Response
    {
        $this->suspensionService->toggle($server, SuspensionService::ACTION_UNSUSPEND);

        return $this->returnNoContent();
    }

    /**
     * Mark a server as needing to be reinstalled.
     *
     * @param \Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest $request
     * @param \Pterodactyl\Models\Server $server
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Throwable
     */
    public function reinstall(ServerWriteRequest $request, Server $server): Response
    {
        $this->reinstallServerService->handle($server);

        return $this->returnNoContent();
    }
}
