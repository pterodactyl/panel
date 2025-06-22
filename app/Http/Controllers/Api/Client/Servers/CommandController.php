<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Facades\Activity;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Repositories\Wings\DaemonCommandRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\SendCommandRequest;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class CommandController extends ClientApiController
{
    /**
     * CommandController constructor.
     */
    public function __construct(private DaemonCommandRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Send a command to a running server.
     *
     * @throws DaemonConnectionException
     */
    public function index(SendCommandRequest $request, Server $server): Response
    {
        try {
            $this->repository->setServer($server)->send($request->input('command'));
        } catch (DaemonConnectionException $exception) {
            $previous = $exception->getPrevious();

            if ($previous instanceof BadResponseException) {
                if (
                    $previous->getResponse() instanceof ResponseInterface
                    && $previous->getResponse()->getStatusCode() === Response::HTTP_BAD_GATEWAY
                ) {
                    throw new HttpException(Response::HTTP_BAD_GATEWAY, 'Server must be online in order to send commands.', $exception);
                }
            }

            throw $exception;
        }

        Activity::event('server:console.command')->property('command', $request->input('command'))->log();

        return $this->returnNoContent();
    }
}
