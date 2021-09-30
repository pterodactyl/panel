<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use LaravelWebauthn\Facades\Webauthn;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LoginController extends AbstractLoginController
{
    private const SESSION_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

    private const METHOD_TOTP = 'totp';
    private const METHOD_WEBAUTHN = 'webauthn';

    private ViewFactory $view;

    /**
     * LoginController constructor.
     */
    public function __construct(ViewFactory $view) {
        parent::__construct();

        $this->view = $view;
    }

    /**
     * Handle all incoming requests for the authentication routes and render the
     * base authentication view component. React will take over at this point and
     * turn the login area into an SPA.
     */
    public function index(): View
    {
        return $this->view->make('templates/auth.core');
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

        try {
            $username = $request->input('user');

            /** @var \Pterodactyl\Models\User $user */
            $user = User::query()->where($this->getField($username), $username)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
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

        $useTotp = $user->use_totp;
        $webauthnKeys = $user->webauthnKeys()->get();

        if (!$useTotp && count($webauthnKeys) < 1) {
            return $this->sendLoginResponse($user, $request);
        }

        $methods = [];
        if ($useTotp) {
            $methods[] = self::METHOD_TOTP;
        }
        if (count($webauthnKeys) > 0) {
            $methods[] = self::METHOD_WEBAUTHN;
        }

        $token = Str::random(64);

        $request->session()->put('auth_confirmation_token', [
            'user_id' => $user->id,
            'token_value' => $token,
            'expires_at' => CarbonImmutable::now()->addMinutes(5),
        ]);

        $response = [
            'complete' => false,
            'methods' => $methods,
            'confirmation_token' => $token,
        ];

        if (count($webauthnKeys) > 0) {
            $publicKey = Webauthn::getAuthenticateData($user);
            $request->session()->put(self::SESSION_PUBLICKEY_REQUEST, $publicKey);

            $response['webauthn'] = [
                'public_key' => $publicKey,
            ];
        }

        return new JsonResponse($response);
    }
}
