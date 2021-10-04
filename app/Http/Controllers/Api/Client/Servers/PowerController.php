<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\SendPowerRequest;

class PowerController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonPowerRepository
     */
    private $repository;

    /**
     * PowerController constructor.
     */
    public function __construct(DaemonPowerRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Send a power action to a server.
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function index(SendPowerRequest $request, Server $server): Response
    {
        $this->repository->setServer($server)->send(
            $request->input('signal')
        );

        return $this->returnNoContent();
    }
}
