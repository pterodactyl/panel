<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Contracts\Servers\SendsServerCommands;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\HttpException;

final readonly class SendServerCommand implements SendsServerCommands
{
    public function send(Server $server, string $command): void
    {
        try {
            Daemon::server($server)->commands($command);
        } catch (DaemonConnectionException $daemonConnectionException) {
            throw_if($daemonConnectionException->getStatusCode() === Response::HTTP_BAD_GATEWAY, HttpException::class, Response::HTTP_BAD_GATEWAY, 'Server must be online in order to send commands.', $daemonConnectionException);

            throw $daemonConnectionException;
        }
    }
}
