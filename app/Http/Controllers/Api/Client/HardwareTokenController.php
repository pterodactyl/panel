<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Support\Str;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Cache\Repository;
use Pterodactyl\Http\Requests\Api\Client\Account\RegisterWebauthnTokenRequest;
use Pterodactyl\Services\Users\HardwareSecurityKeys\CreatePublicKeyCredentialsService;

class HardwareTokenController extends ClientApiController
{
    private CreatePublicKeyCredentialsService $createPublicKeyCredentials;

    private Repository $cache;

    public function __construct(
        Repository $cache,
        CreatePublicKeyCredentialsService $createPublicKeyCredentials
    ) {
        parent::__construct();

        $this->cache = $cache;
        $this->createPublicKeyCredentials = $createPublicKeyCredentials;
    }

    /**
     * Returns all of the hardware security keys (WebAuthn) that exists for a user.
     */
    public function index(Request $request): array
    {
        return [];
    }

    /**
     * Returns the data necessary for creating a new hardware security key for the
     * user.
     */
    public function create(Request $request): JsonResponse
    {
        $tokenId = Str::random(64);
        $credentials = $this->createPublicKeyCredentials->handle($request->user());

        $this->cache->put("webauthn:$tokenId", [
            'credentials' => $credentials->jsonSerialize(),
            'user_entity' => $credentials->getUser()->jsonSerialize(),
        ], CarbonImmutable::now()->addMinutes(10));

        return new JsonResponse([
            'data' => [
                'token_id' => $tokenId,
                'credentials' => $credentials->jsonSerialize(),
            ],
        ]);
    }

    /**
     * Stores a new key for a user account.
     */
    public function store(RegisterWebauthnTokenRequest $request): JsonResponse
    {
        return new JsonResponse([]);
    }

    /**
     * Removes a WebAuthn key from a user's account.
     */
    public function delete(Request $request, int $webauthnKeyId): JsonResponse
    {
        return new JsonResponse([]);
    }
}
