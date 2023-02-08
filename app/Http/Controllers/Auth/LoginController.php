<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Illuminate\Contracts\View\View;
use Pterodactyl\Models\SecurityKey;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Repositories\SecurityKeys\WebauthnServerRepository;

class LoginController extends AbstractLoginController
{
    private const METHOD_TOTP = 'totp';
    private const METHOD_WEBAUTHN = 'webauthn';

    /**
     * LoginController constructor.
     */
    public function __construct(protected WebauthnServerRepository $webauthnServerRepository)
    {
        parent::__construct();
    }

    /**
     * Handle all incoming requests for the authentication routes and render the
     * base authentication view component. React will take over at this point and
     * turn the login area into an SPA.
     */
    public function index(): View
    {
        return view('templates/auth.core');
    }

    /**
     * Handle a login request to the application.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Webauthn\Exception\InvalidDataException
     */
    public function login(Request $request): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        try {
            $username = $request->input('user');

            /** @var \Pterodactyl\Models\User $user */
            $user = User::query()->where($this->getField($username), $username)->firstOrFail();
        } catch (ModelNotFoundException) {
            $this->sendFailedLoginResponse($request);
        }

        // Ensure that the account is using a valid username and password before trying to
        // continue. Previously this was handled in the 2FA checkpoint, however that has
        // a flaw in which you can discover if an account exists simply by seeing if you
        // can proceed to the next step in the login process.
        if (!password_verify($request->input('password'), $user->password)) {
            $this->sendFailedLoginResponse($request, $user);
        }

        // Return early if the user does not have 2FA enabled, otherwise we will require them
        // to complete a secondary challenge before they can log in.
        if (!$user->has2FAEnabled()) {
            return $this->sendLoginResponse($user, $request);
        }

        Activity::event('auth:checkpoint')->withRequestMetadata()->subject($user)->log();

        $request->session()->put('auth_confirmation_token', [
            'user_id' => $user->id,
            'token_value' => $token = Str::random(64),
            'expires_at' => CarbonImmutable::now()->addMinutes(5),
        ]);

        $response = [
            'complete' => false,
            'methods' => array_values(array_filter([
                $user->use_totp ? self::METHOD_TOTP : null,
                $user->securityKeys->isNotEmpty() ? self::METHOD_WEBAUTHN : null,
            ])),
            'confirm_token' => $token,
        ];

        if ($user->securityKeys->isNotEmpty()) {
            $key = $this->webauthnServerRepository->generatePublicKeyCredentialRequestOptions($user);

            $request->session()->put(SecurityKey::PK_SESSION_NAME, $key);

            $request['webauthn'] = ['public_key' => $key];
        }

        return new JsonResponse($response);
    }
}
