<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\ReadsServerLogs;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerLogsRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Overview', 'Inspect and control an accessible server.')]
class LogController extends ClientApiController
{
    private const array LOGS_EXAMPLE = [
        'object' => 'server_logs',
        'attributes' => [
            'lines' => [
                '[12:00:00] [Server thread/INFO]: Starting minecraft server version 1.21',
                '[12:00:04] [Server thread/INFO]: Done (3.512s)! For help, type "help"',
            ],
        ],
    ];

    private const array DAEMON_ERROR = [
        'errors' => [
            [
                'code' => 'DaemonConnectionException',
                'status' => '502',
                'detail' => 'There was an error while communicating with the machine running this server.',
            ],
        ],
    ];

    /**
     * Return the most recent console output of a server, oldest line first.
     *
     * @return array{object: string, attributes: array{lines: list<string>}}
     *
     * @throws DaemonConnectionException
     */
    #[Endpoint('Get server logs', 'Returns the most recent console output lines Wings holds for the server, oldest first.')]
    #[QueryParam('lines', 'integer', 'Number of lines to return. Defaults to the maximum of 100.', required: false, example: 50)]
    #[ScribeResponse(self::LOGS_EXAMPLE, description: 'Console output returned.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not read the console output.')]
    public function __invoke(GetServerLogsRequest $request, ReadsServerLogs $logs, Server $server): array
    {
        $lines = JsonValueGuard::integer($request->validated('lines') ?? ReadsServerLogs::MAX_LINES);

        return [
            'object' => 'server_logs',
            'attributes' => [
                'lines' => $logs->read($server, $lines),
            ],
        ];
    }
}
