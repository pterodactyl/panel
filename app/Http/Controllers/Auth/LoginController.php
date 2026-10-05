<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Unauthenticated;
use Pterodactyl\Contracts\Users\CompletesLogins;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;

#[Group('Authentication', 'Browser session authentication endpoints used by the panel frontend.', authenticated: false)]
class LoginController extends AbstractLoginController
{
    private const array LOGIN_COMPLETE_EXAMPLE = [
        'data' => [
            'complete' => true,
            'intended' => '/',
            'user' => [
                'uuid' => '9a8b7c6d-5e4f-4321-9876-123456789abc',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'name_first' => 'Admin',
                'name_last' => 'User',
                'language' => 'en',
                'root_admin' => true,
                'use_totp' => false,
                'created_at' => '2026-06-29T12:00:00+00:00',
                'updated_at' => '2026-06-29T12:00:00+00:00',
                'identifier' => 'usr_1a2b3c4d',
            ],
        ],
    ];

    private const array LOGIN_CHECKPOINT_EXAMPLE = [
        'data' => [
            'complete' => false,
            'confirmation_token' => 'b6e22f85d63c4d9db9739f9ab0a27a48f51f7ad38ef8c12fd157c4c6f4b2e51d',
        ],
    ];

    private const array LOGIN_FAILED_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'These credentials do not match our records.',
            ],
        ],
    ];

    private const array CAPTCHA_FAILED_ERROR = [
        'errors' => [
            [
                'code' => 'HttpException',
                'status' => '400',
                'detail' => 'Failed to validate reCAPTCHA data.',
            ],
        ],
    ];

    /**
     * Handle all incoming requests for the authentication routes and render the
     * base authentication view component. React will take over at this point and
     * turn the login area into an SPA.
     */
    public function index(): View
    {
        return view('templates/auth.core');
    }

    #[Endpoint('Logout', 'Destroys the current browser session and regenerates the CSRF token.')]
    #[ScribeResponse(status: 204, description: 'The browser session was destroyed.')]
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->wantsJson()
            ? new JsonResponse([], JsonResponse::HTTP_NO_CONTENT)
            : new RedirectResponse('/');
    }

    /**
     * Handle a login request to the application.
     *
     * @throws DisplayException
     * @throws ValidationException
     */
    #[Unauthenticated]
    #[Endpoint('Login', 'Authenticates a browser session. Returns a complete login payload, or a two-factor checkpoint token when the account requires a second factor.')]
    #[BodyParam('user', 'string', 'The account username or email address.', required: true, example: 'admin')]
    #[BodyParam('password', 'string', 'The account password.', required: true, example: 'correct-horse-battery-staple')]
    #[BodyParam('g-recaptcha-response', 'string', 'The reCAPTCHA token when reCAPTCHA is enabled.', required: false, example: '03AFcWeA...', nullable: true)]
    #[ScribeResponse(self::LOGIN_COMPLETE_EXAMPLE, description: 'Login completed and a browser session was created.')]
    #[ScribeResponse(self::LOGIN_CHECKPOINT_EXAMPLE, description: 'Two-factor checkpoint is required before login can complete.')]
    #[ScribeResponse(self::LOGIN_FAILED_ERROR, status: 400, description: 'The credentials are invalid.')]
    #[ScribeResponse(self::CAPTCHA_FAILED_ERROR, status: 400, description: 'The reCAPTCHA token is invalid or missing.')]
    public function login(Request $request, CompletesLogins $logins): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        $username = JsonValueGuard::nullableString($request->input('user'));

        $user = User::query()->where($this->getField($username), $username)->first();
        if (($user) === null) {
            $this->sendFailedLoginResponse($request);
        }

        // Ensure that the account is using a valid username and password before trying to
        // continue. Previously this was handled in the 2FA checkpoint, however that has
        // a flaw in which you can discover if an account exists simply by seeing if you
        // can proceed to the next step in the login process.
        if (! Hash::check(JsonValueGuard::string($request->input('password')), $user->password)) {
            $this->sendFailedLoginResponse($request, $user);
        }

        return $this->sendLoginResponse($logins->complete($user), $request);
    }
}
