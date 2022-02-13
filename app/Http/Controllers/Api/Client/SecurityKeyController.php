<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nyholm\Psr7\Factory\Psr17Factory;
use Illuminate\Contracts\Cache\Repository;
use Psr\Http\Message\ServerRequestInterface;
use Pterodactyl\Exceptions\DisplayException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Pterodactyl\Transformers\Api\Client\SecurityKeyTransformer;
use Pterodactyl\Repositories\SecurityKeys\WebauthnServerRepository;
use Pterodactyl\Services\Users\SecurityKeys\StoreSecurityKeyService;
use Pterodactyl\Http\Requests\Api\Client\Account\RegisterWebauthnTokenRequest;
use Pterodactyl\Services\Users\SecurityKeys\CreatePublicKeyCredentialsService;

class SecurityKeyController extends ClientApiController
{
    protected CreatePublicKeyCredentialsService $createPublicKeyCredentials;

    protected Repository $cache;

    protected WebauthnServerRepository $webauthnServerRepository;

    protected StoreSecurityKeyService $storeSecurityKeyService;

    public function __construct(
        Repository $cache,
        WebauthnServerRepository $webauthnServerRepository,
        StoreSecurityKeyService $storeSecurityKeyService,
        CreatePublicKeyCredentialsService $createPublicKeyCredentials
    ) {
        parent::__construct();

        $this->cache = $cache;
        $this->webauthnServerRepository = $webauthnServerRepository;
        $this->createPublicKeyCredentials = $createPublicKeyCredentials;
        $this->storeSecurityKeyService = $storeSecurityKeyService;
    }

    /**
     * Returns all of the hardware security keys (WebAuthn) that exists for a user.
     */
    public function index(Request $request): array
    {
        return $this->fractal->collection($request->user()->securityKeys)
            ->transformWith(SecurityKeyTransformer::class)
            ->toArray();
    }

    /**
     * Returns the data necessary for creating a new hardware security key for the
     * user.
     */
    public function create(Request $request): JsonResponse
    {
        $tokenId = Str::random(64);
        $credentials = $this->createPublicKeyCredentials->handle($request->user());

        // TODO: session
        $this->cache->put(
            "register-security-key:$tokenId",
            serialize($credentials),
            CarbonImmutable::now()->addMinutes(10)
        );

        return new JsonResponse([
            'data' => [
                'token_id' => $tokenId,
                'credentials' => $credentials->jsonSerialize(),
            ],
        ]);
    }

    /**
     * Stores a new key for a user account.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Throwable
     */
    public function store(RegisterWebauthnTokenRequest $request): array
    {
        $credentials = unserialize(
            $this->cache->pull("register-security-key:{$request->input('token_id')}", serialize(null))
        );

        if (
            !is_object($credentials) ||
            !$credentials instanceof PublicKeyCredentialCreationOptions ||
            $credentials->getUser()->getId() !== $request->user()->uuid
        ) {
            throw new DisplayException('Could not register security key: invalid data present in session, please try again.');
        }

        $key = $this->storeSecurityKeyService
            ->setRequest($this->getServerRequest($request))
            ->setKeyName($request->input('name'))
            ->handle($request->user(), $request->input('registration'), $credentials);

        return $this->fractal->item($key)
            ->transformWith(SecurityKeyTransformer::class)
            ->toArray();
    }

    /**
     * Removes a WebAuthn key from a user's account.
     */
    public function delete(Request $request, string $securityKey): JsonResponse
    {
        $request->user()->securityKeys()->where('uuid', $securityKey)->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    protected function getServerRequest(Request $request): ServerRequestInterface
    {
        $factory = new Psr17Factory();

        $httpFactory = new PsrHttpFactory($factory, $factory, $factory, $factory);

        return $httpFactory->createRequest($request);
    }
}
