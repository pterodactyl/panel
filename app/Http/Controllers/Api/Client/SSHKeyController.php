<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Requests\Api\Client\Account\DeleteSSHKeyRequest;
use Pterodactyl\Http\Requests\Api\Client\Account\StoreSSHKeyRequest;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Transformers\Api\Client\UserSSHKeyTransformer;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Account SSH Keys', 'Manage SSH public keys for the authenticated user.')]
class SSHKeyController extends ClientApiController
{
    /**
     * Returns all the SSH keys that have been configured for the logged-in
     * user account.
     *
     * @return ApiPayload
     */
    #[Endpoint('List SSH keys', 'Returns all SSH public keys configured for the authenticated user.')]
    #[ResponseFromTransformer(UserSSHKeyTransformer::class, UserSSHKey::class, description: 'SSH keys returned.', collection: true, resourceKey: 'ssh_key')]
    public function index(ClientApiRequest $request): array
    {
        return Fractal::collection($request->user()->sshKeys)
            ->transformWith($this->getTransformer(UserSSHKeyTransformer::class))
            ->toResponseArray();
    }

    /**
     * Stores a new SSH key for the authenticated user's account.
     *
     * @return ApiPayload
     */
    #[Endpoint('Create SSH key', 'Adds an SSH public key to the authenticated user account.')]
    #[ResponseFromTransformer(UserSSHKeyTransformer::class, UserSSHKey::class, description: 'SSH key created.', resourceKey: 'ssh_key')]
    public function store(StoreSSHKeyRequest $request): array
    {
        $model = $request->user()->sshKeys()->create([
            'name' => $request->validated('name'),
            'public_key' => $request->getPublicKey(),
            'fingerprint' => $request->getKeyFingerprint(),
        ]);

        Activity::event('user:ssh-key.create')
            ->subject($model)
            ->property('fingerprint', $request->getKeyFingerprint())
            ->log();

        return Fractal::item($model)
            ->transformWith($this->getTransformer(UserSSHKeyTransformer::class))
            ->toResponseArray();
    }

    /**
     * Deletes an SSH key from the user's account.
     */
    #[Endpoint('Delete SSH key', 'Deletes an SSH key from the authenticated user account by fingerprint. Missing keys are treated as already deleted.')]
    #[ScribeResponse(status: 204, description: 'SSH key deleted or already absent.')]
    public function delete(DeleteSSHKeyRequest $request): JsonResponse
    {
        $key = $request->user()->sshKeys()
            ->where('fingerprint', $request->validated('fingerprint'))
            ->first();

        if (($key) !== null) {
            $key->delete();

            Activity::event('user:ssh-key.delete')
                ->subject($key)
                ->property('fingerprint', $key->fingerprint)
                ->log();
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
