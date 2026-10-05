<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Api\CreatesAccountApiKeys;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Requests\Api\Client\Account\StoreApiKeyRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Client\ApiKeyTransformer;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Account API Keys', 'Manage client API keys for the authenticated user.')]
class ApiKeyController extends ClientApiController
{
    private const array LIMIT_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'You have reached the account limit for number of API keys.',
            ],
        ],
    ];

    /**
     * Returns all the API keys that exist for the given client.
     *
     * @return ApiPayload
     */
    #[Endpoint('List account API keys', 'Returns all client API keys owned by the authenticated user.')]
    #[ResponseFromTransformer(ApiKeyTransformer::class, ApiKey::class, description: 'API keys returned.', collection: true, factoryStates: ['withAllowedIps'], resourceKey: 'api_key')]
    public function index(ClientApiRequest $request): array
    {
        return Fractal::collection($request->user()->apiKeys)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->toResponseArray();
    }

    /**
     * Store a new API key for a user's account.
     *
     *
     * @return ApiPayload
     *
     * @throws DisplayException
     */
    #[Endpoint('Create account API key', 'Creates a client API key for the authenticated user and returns the secret token once.')]
    #[ResponseFromTransformer(ApiKeyTransformer::class, ApiKey::class, description: 'API key created.', factoryStates: ['withAllowedIps'], resourceKey: 'api_key', meta: ['secret_token' => 'ptlc_1234567890abcdef1234567890abcdef1234567890abcdef'])]
    #[ScribeResponse(self::LIMIT_ERROR, status: 400, description: 'The account already has the maximum number of API keys.')]
    public function store(StoreApiKeyRequest $request, CreatesAccountApiKeys $creation): array
    {
        $token = $creation->create(
            $request->user(),
            JsonValueGuard::nullableString($request->validated('description')),
            $request->validated('allowed_ips') === null ? null : JsonValueGuard::stringList($request->validated('allowed_ips')),
        );

        Activity::event('user:api-key.create')
            ->subject($token->accessToken)
            ->property('identifier', $token->accessToken->identifier)
            ->log();

        return Fractal::item($token->accessToken)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->addMeta(['secret_token' => $token->plainTextToken])
            ->toResponseArray();
    }

    /**
     * Deletes a given API key.
     */
    #[Endpoint('Delete account API key', 'Deletes one client API key owned by the authenticated user.')]
    #[ScribeResponse(status: 204, description: 'API key deleted.')]
    public function delete(ClientApiRequest $request, string $identifier): JsonResponse
    {
        $key = $request->user()->apiKeys()
            ->where('key_type', ApiKey::TYPE_ACCOUNT)
            ->where('identifier', $identifier)
            ->firstOrFail();

        Activity::event('user:api-key.delete')
            ->property('identifier', $key->identifier)
            ->log();

        $key->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
