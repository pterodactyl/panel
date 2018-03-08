<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\SendCommandRequest;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Contracts\Repository\Daemon\CommandRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class CommandController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\CommandRepositoryInterface
     */
    private $repository;

    /**
     * CommandController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\CommandRepositoryInterface $repository
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService           $keyProviderService
     */
    public function __construct(CommandRepositoryInterface $repository, DaemonKeyProviderService $keyProviderService)
    {
        parent::__construct();

        $this->keyProviderService = $keyProviderService;
        $this->repository = $repository;
    }

    /**
     * Send a command to a running server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\SendCommandRequest $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function index(SendCommandRequest $request): Response
    {
        $server = $request->getModel(Server::class);
        $token = $this->keyProviderService->handle($server, $request->user());

        try {
            $this->repository->setServer($server)
                ->setToken($token)
                ->send($request->input('command'));
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
