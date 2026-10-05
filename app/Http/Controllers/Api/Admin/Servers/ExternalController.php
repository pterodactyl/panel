<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\GetServersRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerFinderService;
use Pterodactyl\Transformers\Api\Admin\ServerTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class ExternalController extends AdminApiController
{
    /**
     * Show server by external identifier.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get server by external ID', 'Returns a single server by external identifier.')]
    #[ResponseFromTransformer(ServerTransformer::class, Server::class, description: 'Server returned.', factoryStates: ['withRelationships'], resourceKey: 'server')]
    public function __invoke(GetServersRequest $request, ServerFinderService $finder, string $external_id): array
    {
        $server = $finder->byExternalId($external_id);

        return Fractal::item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toResponseArray();
    }
}
