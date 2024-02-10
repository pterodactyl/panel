<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Transformers\Api\Client\UserSSHKeyTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\StoreSSHKeyRequest;

class SSHKeyController extends ClientApiController
{
    /**
     * Returns all the SSH keys that have been configured for the logged-in
     * user account.
     */
    public function index(ClientApiRequest $request): array
    {
        return $this->fractal->collection($request->user()->sshKeys)
            ->transformWith(UserSSHKeyTransformer::class)
            ->toArray();
    }

    /**
     * Stores a new SSH key for the authenticated user's account.
     */
    public function store(StoreSSHKeyRequest $request): array
    {
        $model = $request->user()->sshKeys()->create([
            'name' => $request->input('name'),
            'public_key' => $request->getPublicKey(),
            'fingerprint' => $request->getKeyFingerprint(),
        ]);

        Activity::event('user:ssh-key.create')
            ->subject($model)
            ->property('fingerprint', $request->getKeyFingerprint())
            ->log();

        return $this->fractal->item($model)
            ->transformWith(UserSSHKeyTransformer::class)
            ->toArray();
    }

    /**
     * Deletes an SSH key from the user's account.
     */
    public function delete(ClientApiRequest $request): JsonResponse
    {
        $this->validate($request, ['fingerprint' => ['required', 'string']]);

        $key = $request->user()->sshKeys()
            ->where('fingerprint', $request->input('fingerprint'))
            ->first();

        if (!is_null($key)) {
            $key->delete();

            Activity::event('user:ssh-key.delete')
                ->subject($key)
                ->property('fingerprint', $key->fingerprint)
                ->log();
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
