<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\UpdatesServerDetails;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\UpdateServerDetailsRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Admin\ServerTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class DetailsController extends AdminApiController
{
    /**
     * Update server details.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update server details', 'Updates a server name, owner, description, and external identifier.')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, description: 'Server updated.', factoryStates: ['withRelationships'], resourceKey: 'server')]
    public function __invoke(UpdateServerDetailsRequest $request, UpdatesServerDetails $details, Server $server): array
    {
        $updated = $details->update(
            $server,
            $request->payload()
        );

        Activity::event('admin:server.details')
            ->subject($updated)
            ->property('name', $updated->name)
            ->log();

        return Fractal::item($updated)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toResponseArray();
    }
}
