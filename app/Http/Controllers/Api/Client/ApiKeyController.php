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
use Pterodactyl\Transformers\Api\Client\PersonalAccessTokenTransformer;

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
        return $this->fractal->collection($request->user()->tokens)
            ->transformWith($this->getTransformer(PersonalAccessTokenTransformer::class))
            ->toArray();
    }

    /**
     * Store a new API key for a user's account.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(StoreApiKeyRequest $request): array
    {
        if ($request->user()->tokens->count() >= 10) {
            throw new DisplayException('You have reached the account limit for number of API keys.');
        }

        // TODO: this should accept an array of different scopes to apply as permissions
        //  for the token. Right now it allows any account level permission.
        [$token, $plaintext] = $request->user()->createToken($request->input('description'));

        return $this->fractal->item($token)
            ->transformWith($this->getTransformer(PersonalAccessTokenTransformer::class))
            ->addMeta([
                'secret_token' => $plaintext,
            ])
            ->toArray();
    }

    /**
     * Deletes a given API key.
     */
    public function delete(ClientApiRequest $request, string $id): Response
    {
        $request->user()->tokens()->where('token_id', $id)->delete();

        return $this->returnNoContent();
    }
}
