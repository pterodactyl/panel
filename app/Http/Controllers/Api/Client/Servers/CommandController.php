<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\SendCommandRequest;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class CommandController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonCommandRepository
     */
    private $repository;

    /**
     * CommandController constructor.
     *
     * @param \Pterodactyl\Repositories\Wings\DaemonCommandRepository $repository
     */
    public function __construct(DaemonCommandRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Send a command to a running server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\SendCommandRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function index(SendCommandRequest $request): Response
    {
        $server = $request->getModel(Server::class);

        try {
            $this->repository->setServer($server)->send($request->input('command'));
        } catch (RequestException $exception) {
            if ($exception instanceof ClientException) {
                if ($exception->getResponse() instanceof ResponseInterface && $exception->getResponse()->getStatusCode() === 412) {
                    throw new PreconditionFailedHttpException('Server is not online.');
                }
            }

            throw new DaemonConnectionException($exception);
        }

        return $this->returnNoContent();
    }
}
