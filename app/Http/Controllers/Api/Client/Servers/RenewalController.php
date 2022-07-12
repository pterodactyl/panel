<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Servers\ServerRenewalService;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class RenewalController extends ClientApiController
{
    public ServerRenewalService $renewalService;

    public function __construct(ServerRenewalService $renewalService)
    {
        parent::__construct();

        $this->renewalService = $renewalService;
    }

    /**
     * Renew a server.
     */
    public function index(ClientApiRequest $request, Server $server): JsonResponse
    {
        try {
            $this->renewalService->handle($request, $server);
        } catch (DisplayException $ex) {
            throw new DisplayException('Unable to renew server.');
        }
    
        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
