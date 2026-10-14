<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\UpdatesServerBuild;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\UpdateServerBuildConfigurationRequest;
use Pterodactyl\Http\Resources\Api\Admin\ServerResource;
use Pterodactyl\Models\Server;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class BuildController extends AdminApiController
{
    /**
     * Update server build configuration.
     */
    #[Endpoint('Update server build', 'Updates server limits, primary allocation, and feature limits.')]
    #[ResponseFromResource(ServerResource::class, Server::class, description: 'Server build updated.', factoryStates: ['withRelationships'], resourceKey: 'server')]
    public function __invoke(UpdateServerBuildConfigurationRequest $request, UpdatesServerBuild $build, Server $server): ServerResource
    {
        $server = $build->update($server, $request->actionInput());

        Activity::event('admin:server.build')
            ->subject($server)
            ->property('name', $server->name)
            ->log();

        return ServerResource::make($server);
    }
}
