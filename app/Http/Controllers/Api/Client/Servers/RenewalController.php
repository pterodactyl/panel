<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\ServerRenewalService;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class RenewalController extends ClientApiController
{
    public function __construct(private ServerRenewalService $renewalService)
    {
        parent::__construct();
    }

    /**
     * Renew a server.
     */
    public function index(ClientApiRequest $request, Server $server): JsonResponse
    {
        $this->renewalService->handle($request, $server);
    
        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
