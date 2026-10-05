<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\GetServerRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Transformers\Api\Admin\ServerTransferTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class TransferProgressController extends AdminApiController
{
    /**
     * Return server transfer progress.
     *
     * @return ApiPayload|Response
     */
    #[Endpoint('Get server transfer progress', 'Returns the active transfer for a server, or 204 when no transfer is in progress.')]
    #[ResponseFromTransformer(ServerTransferTransformer::class, ServerTransfer::class, description: 'Transfer progress returned.', resourceKey: 'server_transfer')]
    #[ScribeResponse(status: 204, description: 'No server transfer is currently in progress.')]
    public function __invoke(GetServerRequest $request, Server $server): array|Response
    {
        $transfer = $server->transfer;

        if (($transfer) === null) {
            return $this->returnNoContent();
        }

        return Fractal::item($transfer)
            ->transformWith($this->getTransformer(ServerTransferTransformer::class))
            ->toResponseArray();
    }
}
