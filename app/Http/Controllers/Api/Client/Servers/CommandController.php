<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\SendsServerCommands;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\SendCommandRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Overview', 'Inspect and control an accessible server.')]
class CommandController extends ClientApiController
{
    private const array OFFLINE_ERROR = [
        'errors' => [
            [
                'code' => 'HttpException',
                'status' => '502',
                'detail' => 'Server must be online in order to send commands.',
            ],
        ],
    ];

    /**
     * Send a command to a running server.
     *
     * @throws DaemonConnectionException
     */
    #[Endpoint('Send server command', 'Sends a console command to the running server through Wings.')]
    #[ScribeResponse(status: 204, description: 'Command accepted by Wings.')]
    #[ScribeResponse(self::OFFLINE_ERROR, status: 502, description: 'The server is not online or Wings rejected the command.')]
    public function index(SendCommandRequest $request, SendsServerCommands $commands, Server $server): Response
    {
        $command = JsonValueGuard::string($request->validated('command'));
        $commands->send($server, $command);

        Activity::event('server:console.command')->property('command', $command)->log();

        return $this->returnNoContent();
    }
}
