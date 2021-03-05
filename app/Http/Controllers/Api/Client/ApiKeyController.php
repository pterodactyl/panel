<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Response;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Repositories\Eloquent\ApiKeyRepository;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\ApiKeyTransformer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pterodactyl\Http\Requests\Api\Client\Account\StoreApiKeyRequest;

class ApiKeyController extends ClientApiController
{
    private Encrypter $encrypter;
    private ApiKeyRepository $repository;
    private KeyCreationService $keyCreationService;

    /**
     * ApiKeyController constructor.
     */
    public function __construct(
        Encrypter $encrypter,
        ApiKeyRepository $repository,
        KeyCreationService $keyCreationService
    ) {
        parent::__construct();

        $this->encrypter = $encrypter;
        $this->repository = $repository;
        $this->keyCreationService = $keyCreationService;
    }

    /**
     * Returns all of the API keys that exist for the given client.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(ClientApiRequest $request): array
    {
        return $this->fractal->collection($request->user()->apiKeys)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->toArray();
    }

    /**
     * Store a new API key for a user's account.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(StoreApiKeyRequest $request): array
    {
        if ($request->user()->apiKeys->count() >= 5) {
            throw new DisplayException('You have reached the account limit for number of API keys.');
        }

        $key = $this->keyCreationService->setKeyType(ApiKey::TYPE_ACCOUNT)->handle([
            'user_id' => $request->user()->id,
            'memo' => $request->input('description'),
            'allowed_ips' => $request->input('allowed_ips') ?? [],
        ]);

        return $this->fractal->item($key)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->addMeta([
                'secret_token' => $this->encrypter->decrypt($key->token),
            ])
            ->toArray();
    }

    /**
     * Deletes a given API key.
     */
    public function delete(ClientApiRequest $request, string $identifier): Response
    {
        $response = $this->repository->deleteWhere([
            'key_type' => ApiKey::TYPE_ACCOUNT,
            'user_id' => $request->user()->id,
            'identifier' => $identifier,
        ]);

        if (!$response) {
            throw new NotFoundHttpException();
        }

        return $this->returnNoContent();
    }
}
