<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\GetExternalServerRequest;
use Pterodactyl\Http\Resources\Api\Application\ServerResource;
use Pterodactyl\Models\Server;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Servers', 'Create, update, retrieve, manage, and delete servers.')]
class ExternalServerController extends ApplicationApiController
{
    /**
     * Retrieve a specific server from the database using its external ID.
     */
    #[Endpoint('Get server by external ID', 'Returns a single server by its external identifier.')]
    #[ResponseFromResource(ServerResource::class, Server::class, factoryStates: ['withRelationships'], resourceKey: 'server')]
    public function index(GetExternalServerRequest $request, string $external_id): ServerResource
    {
        $server = Server::query()->where('external_id', $external_id)->firstOrFail();

        return ServerResource::make($server);
    }
}
