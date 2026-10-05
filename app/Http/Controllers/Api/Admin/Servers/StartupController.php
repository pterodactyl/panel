<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\UpdatesServerStartup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\UpdateServerStartupRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Transformers\Api\Admin\ServerTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class StartupController extends AdminApiController
{
    /**
     * Update server startup configuration.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update server startup', 'Updates the egg, Docker image, startup command, environment, and install-script behavior.')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, description: 'Server startup updated.', factoryStates: ['withRelationships'], resourceKey: 'server')]
    public function __invoke(UpdateServerStartupRequest $request, UpdatesServerStartup $modification, Server $server): array
    {
        $server = $modification->update($server, $request->payload(), User::USER_LEVEL_ADMIN);

        Activity::event('admin:server.startup')
            ->subject($server)
            ->property('name', $server->name)
            ->log();

        return Fractal::item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toResponseArray();
    }
}
