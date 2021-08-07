<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Response;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Http\Requests\Api\Client\AccountApiRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\StoreApiKeyRequest;
use Pterodactyl\Transformers\Api\Client\PersonalAccessTokenTransformer;

class ApiKeyController extends ClientApiController
{
    /**
     * Returns all of the API keys that exist for the given client.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(AccountApiRequest $request): array
    {
        return $this->fractal->collection($request->user()->tokens)
            ->transformWith(PersonalAccessTokenTransformer::class)
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
        $token = $request->user()->createToken($request->input('description'));

        return $this->fractal->item($token->accessToken)
            ->transformWith(PersonalAccessTokenTransformer::class)
            ->addMeta([
                'secret_token' => $token->plainTextToken,
            ])
            ->toArray();
    }

    /**
     * Deletes a given API key.
     */
    public function delete(AccountApiRequest $request, string $id): Response
    {
        $request->user()->tokens()->where('token_id', $id)->delete();

        return $this->returnNoContent();
    }
}
