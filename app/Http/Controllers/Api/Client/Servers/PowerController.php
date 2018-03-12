<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\SendPowerRequest;
use Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface;

class PowerController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface
     */
    private $repository;

    /**
     * PowerController constructor.
     *
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService         $keyProviderService
     * @param \Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface $repository
     */
    public function __construct(DaemonKeyProviderService $keyProviderService, PowerRepositoryInterface $repository)
    {
        parent::__construct();

        $this->keyProviderService = $keyProviderService;
        $this->repository = $repository;
    }

    /**
     * Send a power action to a server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\SendPowerRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Repository\Daemon\InvalidPowerSignalException
     */
    public function index(SendPowerRequest $request): Response
    {
        $server = $request->getModel(Server::class);
        $token = $this->keyProviderService->handle($server, $request->user());

        $this->repository->setServer($server)->setToken($token)->sendSignal($request->input('signal'));

        return $this->returnNoContent();
    }
}
