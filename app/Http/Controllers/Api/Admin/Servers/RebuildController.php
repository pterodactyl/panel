<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\RebuildsServers;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\ServerWriteRequest;
use Pterodactyl\Models\Server;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class RebuildController extends AdminApiController
{
    /**
     * Rebuild server on daemon.
     */
    #[Endpoint('Rebuild server', 'Synchronizes the server configuration to Wings.')]
    #[ScribeResponse(status: 204, description: 'Server rebuild synchronized.')]
    public function __invoke(ServerWriteRequest $request, RebuildsServers $rebuild, Server $server): Response
    {
        $rebuild->rebuild($server);

        Activity::event('admin:server.rebuild')
            ->subject($server)
            ->property('name', $server->name)
            ->log();

        return $this->returnNoContent();
    }
}
