<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\ReinstallsServers;
use Pterodactyl\Contracts\Servers\TogglesServerSuspension;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Pterodactyl\Models\Server;
use Throwable;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Servers', 'Create, update, retrieve, manage, and delete servers.')]
class ServerManagementController extends ApplicationApiController
{
    private const array TRANSFER_CONFLICT_ERROR = [
        'errors' => [
            [
                'code' => 'ConflictHttpException',
                'status' => '409',
                'detail' => 'Cannot toggle suspension status on a server that is currently being transferred.',
            ],
        ],
    ];

    private const array DAEMON_CONNECTION_ERROR = [
        'errors' => [
            [
                'code' => 'DaemonConnectionException',
                'status' => '504',
                'detail' => 'Could not establish a connection to the machine running this server. Please try again.',
            ],
        ],
    ];

    /**
     * Suspend a server on the Panel.
     *
     * @throws Throwable
     */
    #[Endpoint('Suspend server', 'Marks a server as suspended and syncs the state to Wings.')]
    #[ScribeResponse(status: 204, description: 'Server suspended.')]
    #[ScribeResponse(self::TRANSFER_CONFLICT_ERROR, status: 409, description: 'The server is currently being transferred.')]
    #[ScribeResponse(self::DAEMON_CONNECTION_ERROR, status: 504, description: 'Wings could not be reached while syncing suspension state.')]
    public function suspend(ServerWriteRequest $request, TogglesServerSuspension $suspension, Server $server): Response
    {
        $suspension->toggle($server);

        return $this->returnNoContent();
    }

    /**
     * Unsuspend a server on the Panel.
     *
     * @throws Throwable
     */
    #[Endpoint('Unsuspend server', 'Clears a server suspension and syncs the state to Wings.')]
    #[ScribeResponse(status: 204, description: 'Server unsuspended.')]
    #[ScribeResponse(self::TRANSFER_CONFLICT_ERROR, status: 409, description: 'The server is currently being transferred.')]
    #[ScribeResponse(self::DAEMON_CONNECTION_ERROR, status: 504, description: 'Wings could not be reached while syncing suspension state.')]
    public function unsuspend(ServerWriteRequest $request, TogglesServerSuspension $suspension, Server $server): Response
    {
        $suspension->toggle($server, TogglesServerSuspension::ACTION_UNSUSPEND);

        return $this->returnNoContent();
    }

    /**
     * Mark a server as needing to be reinstalled.
     *
     * @throws DisplayException
     */
    #[Endpoint('Reinstall server', 'Marks a server for reinstall and asks Wings to reinstall it.')]
    #[ScribeResponse(status: 204, description: 'Server marked for reinstall.')]
    #[ScribeResponse(self::DAEMON_CONNECTION_ERROR, status: 504, description: 'Wings could not be reached while starting reinstall.')]
    public function reinstall(ServerWriteRequest $request, ReinstallsServers $reinstall, Server $server): Response
    {
        $reinstall->reinstall($server);

        return $this->returnNoContent();
    }
}
