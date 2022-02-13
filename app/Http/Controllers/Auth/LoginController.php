<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\SecurityKey;
use Illuminate\Support\Facades\View;
use Illuminate\Contracts\View\View as ViewContract;
use Pterodactyl\Services\Users\SecurityKeys\GeneratePublicKeyCredentialsRequestService;

class LoginController extends AbstractLoginController
{
    private const METHOD_TOTP = 'totp';
    private const METHOD_WEBAUTHN = 'webauthn';
    private const SESSION_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

    protected GeneratePublicKeyCredentialsRequestService $service;

    /**
     * @param \Pterodactyl\Services\Users\SecurityKeys\GeneratePublicKeyCredentialsRequestService $service
     */
    public function __construct(GeneratePublicKeyCredentialsRequestService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    /**
     * Handle all incoming requests for the authentication routes and render the
     * base authentication view component. React will take over at this point and
     * turn the login area into an SPA.
     */
    public function index(): ViewContract
    {
        return View::make('templates/auth.core');
    }

    /**
     * Handle a login request to the application.
     *
     * @return \Illuminate\Http\JsonResponse|void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);

            return;
        }

        $username = $request->input('user');

        /** @var \Pterodactyl\Models\User|null $user */
        $user = User::query()->where($this->getField($username), $username)->first();
        if (is_null($user)) {
            $this->sendFailedLoginResponse($request);
        }

        // Ensure that the account is using a valid username and password before trying to
        // continue. Previously this was handled in the 2FA checkpoint, however that has
        // a flaw in which you can discover if an account exists simply by seeing if you
        // can proceed to the next step in the login process.
        if (!password_verify($request->input('password'), $user->password)) {
            $this->sendFailedLoginResponse($request, $user);

            return;
        }

        if (!$user->use_totp && empty($user->securityKeys)) {
            return $this->sendLoginResponse($user, $request);
        }

        $token = Str::random(64);
        $request->session()->put('auth_confirmation_token', [
            'user_id' => $user->id,
            'token_value' => $token,
            'expires_at' => CarbonImmutable::now()->addMinutes(5),
        ]);

        $response = [
            'complete' => false,
            'methods' => array_values(array_filter([
                $user->use_totp ? self::METHOD_TOTP : null,
                !empty($user->securityKeys) ? self::METHOD_WEBAUTHN : null,
            ])),
            'confirmation_token' => $token,
        ];

        if (!empty($user->securityKeys)) {
            $key = $this->service->handle($user);

            $request->session()->put(self::SESSION_PUBLICKEY_REQUEST, $key);

            $response['webauthn'] = ['public_key' => $key];
        }

        return new JsonResponse($response);
    }
}
