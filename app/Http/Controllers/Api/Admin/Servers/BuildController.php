<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\UpdatesServerBuild;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\UpdateServerBuildConfigurationRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Admin\ServerTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class BuildController extends AdminApiController
{
    /**
     * Update server build configuration.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update server build', 'Updates server limits, primary allocation, and feature limits.')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, description: 'Server build updated.', factoryStates: ['withRelationships'], resourceKey: 'server')]
    public function __invoke(UpdateServerBuildConfigurationRequest $request, UpdatesServerBuild $build, Server $server): array
    {
        $server = $build->update($server, $request->payload());

        Activity::event('admin:server.build')
            ->subject($server)
            ->property('name', $server->name)
            ->log();

        return Fractal::item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toResponseArray();
    }
}
