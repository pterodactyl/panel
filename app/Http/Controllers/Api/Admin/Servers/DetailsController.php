<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\UpdatesServerDetails;
use Pterodactyl\Extensions\Scribe\Attributes\ExtensionFieldsParam;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\UpdateServerDetailsRequest;
use Pterodactyl\Http\Resources\Api\Admin\ServerResource;
use Pterodactyl\Models\Server;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class DetailsController extends AdminApiController
{
    /**
     * Update server details.
     */
    #[Endpoint('Update server details', 'Updates a server name, owner, description, and external identifier.')]
    #[ResponseFromResource(ServerResource::class, Server::class, description: 'Server updated.', factoryStates: ['withRelationships'], resourceKey: 'server')]
    #[ExtensionFieldsParam]
    public function __invoke(UpdateServerDetailsRequest $request, UpdatesServerDetails $details, Server $server): ServerResource
    {
        $updated = $details->update(
            $server,
            $request->actionInput()
        );

        Activity::event('admin:server.details')
            ->subject($updated)
            ->property('name', $updated->name)
            ->log();

        return ServerResource::make($updated)->withExtensionFields();
    }
}
