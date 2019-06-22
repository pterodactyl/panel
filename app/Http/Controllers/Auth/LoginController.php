<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
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

        if ($user->use_totp) {
            $token = str_random(64);
            $this->cache->put($token, ['user_id' => $user->id, 'valid_credentials' => true], 5);

            return redirect()->route('auth.totp')->with('authentication_token', $token);
        }

        $this->auth->guard()->login($user, true);

        return $this->sendLoginResponse($user, $request);
    }

    /**
     * Handle a TOTP implementation page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function totp(Request $request)
    {
        $token = $request->session()->get('authentication_token');
        if (is_null($token) || $this->auth->guard()->user()) {
            return redirect()->route('auth.login');
        }

        return view('auth.totp', ['verify_key' => $token]);
    }

    /**
     * Handle a login where the user is required to provide a TOTP authentication
     * token.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     *
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function loginUsingTotp(Request $request)
    {
        if (is_null($request->input('verify_token'))) {
            return $this->sendFailedLoginResponse($request);
        }

        try {
            $cache = $this->cache->pull($request->input('verify_token'), []);
            $user = $this->repository->find(array_get($cache, 'user_id', 0));
        } catch (RecordNotFoundException $exception) {
            return $this->sendFailedLoginResponse($request);
        }

        if (is_null($request->input('2fa_token'))) {
            return $this->sendFailedLoginResponse($request, $user);
        }

        if (! $this->google2FA->verifyKey(
            $this->encrypter->decrypt($user->totp_secret),
            $request->input('2fa_token'),
            $this->config->get('pterodactyl.auth.2fa.window')
        )) {
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

            return response()->json([
                'complete' => false,
                'login_token' => $token,
            ]);
        }

        return $this->sendLoginResponse($user, $request);
    }
}
