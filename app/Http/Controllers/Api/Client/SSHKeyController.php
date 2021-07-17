<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Transformers\Api\Client\UserSSHKeyTransformer;
use Pterodactyl\Http\Requests\Api\Client\Account\StoreSSHKeyRequest;

class SSHKeyController extends ClientApiController
{
    /**
     * ?
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(Request $request): \Pterodactyl\Extensions\Spatie\Fractalistic\Fractal
    {
        return $this->fractal->collection(UserSSHKey::query()->where('user_id', '=', $request->user()->id)->get())
            ->transformWith($this->getTransformer(UserSSHKeyTransformer::class));
    }

    /**
     * ?
     *
     * @return JsonResponse
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function store(StoreSSHKeyRequest $request): JsonResponse
    {
        if ($request->user()->sshKeys->count() >= 5) {
            throw new DisplayException('You have reached the account limit for number of SSH keys.');
        }

        $data = array_merge($request->validated(), [
            'user_id' => $request->user()->id,
        ]);
        $key = UserSSHKey::query()->create($data);

        return $this->fractal->item($key)
            ->transformWith($this->getTransformer(UserSSHKeyTransformer::class))
            ->respond(JsonResponse::HTTP_CREATED);
    }

    /**
     * ?
     */
    public function delete(Request $request, UserSSHKey $sshKey): Response
    {
        $sshKey->delete();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
