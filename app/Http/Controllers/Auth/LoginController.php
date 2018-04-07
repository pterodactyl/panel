<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class LoginController extends AbstractLoginController
{
    /**
     * Handle a login request to the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $username = $request->input('user');
        $useColumn = $this->getField($username);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        try {
            $user = $this->repository->findFirstWhere([[$useColumn, '=', $username]]);
        } catch (RecordNotFoundException $exception) {
            return $this->sendFailedLoginResponse($request);
        }

        $validCredentials = password_verify($request->input('password'), $user->password);
        if ($user->use_totp) {
            $token = str_random(128);
            $this->cache->put($token, [
                'user_id' => $user->id,
                'valid_credentials' => $validCredentials,
                'request_ip' => $request->ip(),
            ], 5);

            return response()->json(['complete' => false, 'token' => $token]);
        }

        if (! $validCredentials) {
            return $this->sendFailedLoginResponse($request, $user);
        }

        $this->auth->guard()->login($user, true);

        return response()->json(['complete' => true]);
    }
}
