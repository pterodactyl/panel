<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class LoginCheckpointController extends AbstractLoginController
{
    /**
     * Handle a login where the user is required to provide a TOTP authentication
     * token. Once a user has reached this stage it is assumed that they have already
     * provided a valid username and password.
     *
     * @param \Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function __invoke(LoginCheckpointRequest $request): JsonResponse
    {
        try {
            $cache = $this->cache->pull($request->input('confirmation_token'), []);
            $user = $this->repository->find(array_get($cache, 'user_id', 0));
        } catch (RecordNotFoundException $exception) {
            return $this->sendFailedLoginResponse($request);
        }

        if (array_get($cache, 'request_ip') !== $request->ip()) {
            return $this->sendFailedLoginResponse($request, $user);
        }

        if (! $this->google2FA->verifyKey(
            $this->encrypter->decrypt($user->totp_secret),
            $request->input('authentication_code'),
            config('pterodactyl.auth.2fa.window')
        )) {
            return $this->sendFailedLoginResponse($request, $user);
        }

        $this->auth->guard()->login($user, true);

        return $this->sendLoginResponse($request);
    }
}
