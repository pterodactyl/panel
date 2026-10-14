<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\UpdatesServerBuild;
use Pterodactyl\Contracts\Servers\UpdatesServerDetails;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Scribe\Attributes\ExtensionFieldsParam;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerBuildConfigurationRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\UpdateServerDetailsRequest;
use Pterodactyl\Http\Resources\Api\Application\ServerResource;
use Pterodactyl\Models\Server;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Servers', 'Create, update, retrieve, manage, and delete servers.')]
class ServerDetailsController extends ApplicationApiController
{
    private const array ALLOCATION_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'The requested default allocation is not currently assigned to this server.',
            ],
        ],
    ];

    /**
     * Update the details for a specific server.
     *
     * @throws DisplayException
     */
    #[Endpoint('Update server details', 'Updates ownership and display details for a server.')]
    #[ResponseFromResource(ServerResource::class, Server::class, factoryStates: ['withRelationships'], resourceKey: 'server')]
    #[ExtensionFieldsParam]
    public function details(UpdateServerDetailsRequest $request, UpdatesServerDetails $details, Server $server): ServerResource
    {
        $updated = $details->update(
            $server,
            $request->actionInput()
        );

        return ServerResource::make($updated)->withExtensionFields();
    }

    /**
     * Update the build details for a specific server.
     *
     * @throws DisplayException
     */
    #[Endpoint('Update server build', 'Updates resource limits, feature limits, and allocations for a server.')]
    #[ResponseFromResource(ServerResource::class, Server::class, factoryStates: ['withRelationships'], resourceKey: 'server')]
    #[ScribeResponse(self::ALLOCATION_ERROR, status: 400, description: 'The requested allocation change is invalid.')]
    public function build(UpdateServerBuildConfigurationRequest $request, UpdatesServerBuild $build, Server $server): ServerResource
    {
        $server = $build->update($server, $request->actionInput());

        return ServerResource::make($server)->withExtensionFields();
    }
}
