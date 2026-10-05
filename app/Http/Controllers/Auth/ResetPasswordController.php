<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Unauthenticated;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Auth\ResetPasswordRequest;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;

#[Group('Authentication', 'Browser session authentication endpoints used by the panel frontend.', authenticated: false)]
class ResetPasswordController extends Controller
{
    private const array RESET_COMPLETE_EXAMPLE = [
        'success' => true,
        'redirect_to' => '/',
        'send_to_login' => false,
    ];

    private const array RESET_FAILED_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'This password reset token is invalid.',
            ],
        ],
    ];

    /**
     * The URL to redirect users to after password reset.
     */
    public string $redirectTo = '/';

    protected bool $hasTwoFactor = false;

    /**
     * Reset the given user's password.
     *
     * @throws DisplayException
     */
    #[Unauthenticated]
    #[Endpoint('Reset password', 'Resets an account password using a password reset token. The response tells the frontend whether the user must return to the login form.')]
    #[BodyParam('email', 'string', 'The account email address.', required: true, example: 'admin@example.com')]
    #[BodyParam('token', 'string', 'The password reset token.', required: true, example: 'b6e22f85d63c4d9db9739f9ab0a27a48f51f7ad38ef8c12fd157c4c6f4b2e51d')]
    #[BodyParam('password', 'string', 'The new account password. Must be at least eight characters.', required: true, example: 'correct-horse-battery-staple')]
    #[BodyParam('password_confirmation', 'string', 'Confirmation matching the new account password.', required: true, example: 'correct-horse-battery-staple')]
    #[ScribeResponse(self::RESET_COMPLETE_EXAMPLE, description: 'Password reset completed.')]
    #[ScribeResponse(self::RESET_FAILED_ERROR, status: 400, description: 'The reset token is invalid or expired.')]
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise, we will parse the error and return the response.
        $response = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $this->resetPassword($user, $password);
            }
        );

        if ($response === Password::PASSWORD_RESET) {
            return $this->sendResetResponse();
        }

        throw new DisplayException(trans(JsonValueGuard::nullableString($response)));
    }

    /**
     * Reset the given user's password. If the user has two-factor authentication enabled on their
     * account do not automatically log them in. In those cases, send the user back to the login
     * form with a note telling them their password was changed and to log back in.
     *
     * @param  CanResetPassword&User  $user
     * @param  string  $password
     */
    protected function resetPassword($user, $password): void // @pest-ignore-type
    {
        $user->forceFill([
            'password' => $password,
            $user->getRememberTokenName() => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
        event(new PasswordChanged($user));

        // If the user is not using 2FA log them in, otherwise skip this step and force a
        // fresh login where they'll be prompted to enter a token.
        if (! $user->use_totp) {
            Auth::guard()->login($user);
        }

        $this->hasTwoFactor = $user->use_totp;
    }

    /**
     * Send a successful password reset response back to the callee.
     */
    protected function sendResetResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'redirect_to' => $this->redirectTo,
            'send_to_login' => $this->hasTwoFactor,
        ]);
    }
}
