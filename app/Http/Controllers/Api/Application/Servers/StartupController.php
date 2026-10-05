<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\UpdatesServerStartup;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerStartupRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Servers', 'Create, update, retrieve, manage, and delete servers.')]
class StartupController extends ApplicationApiController
{
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
     * Update the startup and environment settings for a specific server.
     *
     *
     * @return ApiPayload
     *
     * @throws ValidationException
     * @throws DaemonConnectionException
     */
    #[Endpoint('Update server startup', 'Updates startup command, image, egg, and environment variables for a server.')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, factoryStates: ['withRelationships'], resourceKey: 'server')]
    #[ScribeResponse(self::DAEMON_CONNECTION_ERROR, status: 504, description: 'Wings could not be reached while syncing startup changes.')]
    public function index(UpdateServerStartupRequest $request, UpdatesServerStartup $modification, Server $server): array
    {
        $server = $modification->update($server, $request->payload(), User::USER_LEVEL_ADMIN);

        return Fractal::item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toResponseArray();
    }
}
