<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\SSHKeyTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\StoreSSHKeyRequest;

class SSHKeyController extends ClientApiController
{
    /**
     * Returns all of the SSH keys that have been configured for the logged in
     * user account.
     */
    public function index(ClientApiRequest $request): array
    {
        return $this->fractal->collection($request->user()->sshKeys)
            ->transformWith($this->getTransformer(SSHKeyTransformer::class))
            ->toArray();
    }

    /**
     * Stores a new SSH key for the authenticated user's account.
     */
    public function store(StoreSSHKeyRequest $request): array
    {
        $model = $request->user()->sshKeys()->create([
            'name' => $request->input('name'),
            'public_key' => $request->input('public_key'),
            'fingerprint' => $request->getKeyFingerprint(),
        ]);

        return $this->fractal->item($model)
            ->transformWith($this->getTransformer(SSHKeyTransformer::class))
            ->toArray();
    }

    /**
     * Deletes an SSH key from the user's account.
     */
    public function delete(ClientApiRequest $request, string $identifier): JsonResponse
    {
        $request->user()->sshKeys()->where('fingerprint', $identifier)->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
