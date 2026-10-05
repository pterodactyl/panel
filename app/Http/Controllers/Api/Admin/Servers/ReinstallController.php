<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\ReinstallsServers;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\ServerWriteRequest;
use Pterodactyl\Models\Server;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class ReinstallController extends AdminApiController
{
    /**
     * Reinstall server.
     */
    #[Endpoint('Reinstall server', 'Queues a server reinstall through Wings.')]
    #[ScribeResponse(status: 202, description: 'Server reinstall accepted.')]
    public function __invoke(ServerWriteRequest $request, ReinstallsServers $reinstall, Server $server): JsonResponse
    {
        $reinstall->reinstall($server);

        Activity::event('admin:server.reinstall')
            ->subject($server)
            ->property('name', $server->name)
            ->log();

        return new JsonResponse([], Response::HTTP_ACCEPTED);
    }
}
