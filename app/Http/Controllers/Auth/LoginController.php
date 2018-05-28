<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Lcobucci\JWT\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class LoginController extends AbstractLoginController
{
    /**
     * Handle all incoming requests for the authentication routes and render the
     * base authentication view component. Vuejs will take over at this point and
     * turn the login area into a SPA.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        return view('templates/auth.core');
    }

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

        // Ensure that the account is using a valid username and password before trying to
        // continue. Previously this was handled in the 2FA checkpoint, however that has
        // a flaw in which you can discover if an account exists simply by seeing if you
        // can proceede to the next step in the login process.
        if (! password_verify($request->input('password'), $user->password)) {
            return $this->sendFailedLoginResponse($request, $user);
        }

        // If the user is using 2FA we do not actually log them in at this step, we return
        // a one-time token to link the 2FA credentials to this account via the UI.
        if ($user->use_totp) {
            $token = str_random(128);
            $this->cache->put($token, [
                'user_id' => $user->id,
                'request_ip' => $request->ip(),
            ], 5);

            return response()->json(['complete' => false, 'login_token' => $token]);
        }

        $signer = new Sha256();
        $token = (new Builder)->setIssuer('http://pterodactyl.local')
            ->setAudience('http://pterodactyl.local')
            ->setId(str_random(12), true)
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration(time() + 3600)
            ->set('uid', $user->id)
            ->sign($signer, env('APP_JWT_KEY'))
            ->getToken();

        $this->auth->guard()->login($user, true);

        return response()->json([
            'complete' => true,
            'intended' => $this->redirectPath(),
            'token' => $token->__toString(),
        ]);
    }
}
