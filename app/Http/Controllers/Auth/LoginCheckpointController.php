<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Auth;

use Exception;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Unauthenticated;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use Pterodactyl\Contracts\Users\CompletesLogins;
use Pterodactyl\Data\LoginCheckpoint;
use Pterodactyl\Events\Auth\ProvidedAuthenticationToken;
use Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;

#[Group('Authentication', 'Browser session authentication endpoints used by the panel frontend.', authenticated: false)]
class LoginCheckpointController extends AbstractLoginController
{
    private const string TOKEN_EXPIRED_MESSAGE = 'The authentication token provided has expired, please refresh the page and try again.';

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
                'use_totp' => true,
                'created_at' => '2026-06-29T12:00:00+00:00',
                'updated_at' => '2026-06-29T12:00:00+00:00',
                'identifier' => 'usr_1a2b3c4d',
            ],
        ],
    ];

    private const array CHECKPOINT_FAILED_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'The authentication token provided has expired, please refresh the page and try again.',
            ],
        ],
    ];

    /**
     * Handle a login where the user is required to provide a TOTP authentication
     * token. Once a user has reached this stage it is assumed that they have already
     * provided a valid username and password.
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws Exception
     * @throws ValidationException
     */
    #[Unauthenticated]
    #[Endpoint('Complete login checkpoint', 'Completes a two-factor login checkpoint using either a TOTP code or a recovery token.')]
    #[BodyParam('confirmation_token', 'string', 'The confirmation token returned by the login endpoint.', required: true, example: 'b6e22f85d63c4d9db9739f9ab0a27a48f51f7ad38ef8c12fd157c4c6f4b2e51d')]
    #[BodyParam('authentication_code', 'string', 'The six-digit TOTP code. Required when recovery_token is not provided.', required: false, example: '123456', nullable: true)]
    #[BodyParam('recovery_token', 'string', 'A recovery token. Required when authentication_code is not provided.', required: false, example: 'r1a2b3c4d5', nullable: true)]
    #[ScribeResponse(self::LOGIN_COMPLETE_EXAMPLE, description: 'Login completed and a browser session was created.')]
    #[ScribeResponse(self::CHECKPOINT_FAILED_ERROR, status: 400, description: 'The checkpoint token, TOTP code, or recovery token is invalid.')]
    public function __invoke(LoginCheckpointRequest $request, CompletesLogins $logins, Google2FA $google2FA, Encrypter $encrypter): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
        }

        $checkpoint = $logins->pendingCheckpoint();
        if (! $checkpoint instanceof LoginCheckpoint) {
            $this->sendFailedLoginResponse($request, null, self::TOKEN_EXPIRED_MESSAGE);
        }

        if (! $checkpoint->matches(JsonValueGuard::nullableString($request->validated('confirmation_token')) ?? '')) {
            $this->sendFailedLoginResponse($request);
        }

        try {
            $user = User::query()->findOrFail($checkpoint->userId);
        } catch (ModelNotFoundException) {
            $this->sendFailedLoginResponse($request, null, self::TOKEN_EXPIRED_MESSAGE);
        }

        // Recovery tokens go through a slightly different pathway for usage.
        if (($recoveryToken = $request->validated('recovery_token')) !== null) {
            if ($this->isValidRecoveryToken($user, JsonValueGuard::string($recoveryToken))) {
                Event::dispatch(new ProvidedAuthenticationToken($user, true));

                return $this->sendLoginResponse($logins->establish($user), $request);
            }
        } else {
            $totpSecret = $user->totp_secret;
            if ($totpSecret === null) {
                $this->sendFailedLoginResponse($request, $user, self::TOKEN_EXPIRED_MESSAGE);
            }

            $decrypted = JsonValueGuard::string($encrypter->decrypt($totpSecret));
            // SAFETY: TOTP counters are discrete intervals, so flooring and converting the quotient to an integer is required.
            $oldTimestamp = $user->totp_authenticated_at
                ? (int) floor($user->totp_authenticated_at->unix() / $google2FA->getKeyRegeneration())
                : null;

            $verified = $google2FA->verifyKeyNewer(
                $decrypted,
                JsonValueGuard::nullableString($request->validated('authentication_code')) ?? '',
                $oldTimestamp,
                JsonValueGuard::integer(config('pterodactyl.auth.2fa.window', 1))
            );

            if ($verified !== false) {
                $user->update(['totp_authenticated_at' => now()]);

                Event::dispatch(new ProvidedAuthenticationToken($user));

                return $this->sendLoginResponse($logins->establish($user), $request);
            }
        }

        $this->sendFailedLoginResponse($request, $user, ! empty($recoveryToken) ? 'The recovery token provided is not valid.' : null);
    }

    /**
     * Determines if a given recovery token is valid for the user account. If we find a matching token
     * it will be deleted from the database.
     *
     * @throws Exception
     */
    protected function isValidRecoveryToken(User $user, string $value): bool
    {
        foreach ($user->recoveryTokens as $token) {
            if (Hash::check($value, $token->token)) {
                $token->delete();

                return true;
            }
        }

        return false;
    }
}
