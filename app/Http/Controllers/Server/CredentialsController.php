<?php

namespace Pterodactyl\Http\Controllers\Server;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;

class CredentialsController extends Controller
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * CredentialsController constructor.
     *
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService $keyProviderService
     */
    public function __construct(DaemonKeyProviderService $keyProviderService)
    {
        $this->keyProviderService = $keyProviderService;
    }

    /**
     * Return a set of credentials that the currently authenticated user can use to access
     * a given server with.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \Pterodactyl\Models\Server $server */
        $server = $request->attributes->get('server');
        $server->loadMissing('node');

        return JsonResponse::create([
            'node' => $server->getRelation('node')->getConnectionAddress(),
            'key' => $this->keyProviderService->handle($server, $request->user()),
        ]);
    }
}
