<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\GetServerRequest;
use Pterodactyl\Http\Resources\Api\Admin\ServerTransferResource;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class TransferProgressController extends AdminApiController
{
    /**
     * Return server transfer progress.
     */
    #[Endpoint('Get server transfer progress', 'Returns the active transfer for a server, or 204 when no transfer is in progress.')]
    #[ResponseFromResource(ServerTransferResource::class, ServerTransfer::class, description: 'Transfer progress returned.', resourceKey: 'server_transfer')]
    #[ScribeResponse(status: 204, description: 'No server transfer is currently in progress.')]
    public function __invoke(GetServerRequest $request, Server $server): ServerTransferResource|Response
    {
        $transfer = $server->transfer;

        if (($transfer) === null) {
            return $this->returnNoContent();
        }

        return ServerTransferResource::make($transfer);
    }
}
