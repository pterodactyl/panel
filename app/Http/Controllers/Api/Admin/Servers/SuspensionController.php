<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\TogglesServerSuspension;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\UpdateServerSuspensionRequest;
use Pterodactyl\Models\Server;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class SuspensionController extends AdminApiController
{
    /**
     * Update server suspension state.
     */
    #[Endpoint('Update server suspension', 'Suspends or unsuspends a server.')]
    #[ScribeResponse(status: 204, description: 'Server suspension state updated.')]
    public function __invoke(UpdateServerSuspensionRequest $request, TogglesServerSuspension $suspension, Server $server): Response
    {
        $suspended = $request->boolean('suspended');

        $suspension->toggle(
            $server,
            $suspended ? TogglesServerSuspension::ACTION_SUSPEND : TogglesServerSuspension::ACTION_UNSUSPEND
        );

        Activity::event($suspended ? 'admin:server.suspend' : 'admin:server.unsuspend')
            ->subject($server)
            ->property('name', $server->name)
            ->log();

        return $this->returnNoContent();
    }
}
