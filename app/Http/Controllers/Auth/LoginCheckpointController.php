<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Event;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Events\Auth\ProvidedAuthenticationToken;
use Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class LoginCheckpointController extends AbstractLoginController
{
    private const TOKEN_EXPIRED_MESSAGE = 'The authentication token provided has expired, please refresh the page and try again.';

    /**
     * LoginCheckpointController constructor.
     */
    public function __construct(
        private Encrypter $encrypter,
        private Google2FA $google2FA,
        private ValidationFactory $validation
    ) {
        parent::__construct();
    }

    /**
     * Handle a login where the user is required to provide a TOTP authentication
     * token. Once a user has reached this stage it is assumed that they have already
     * provided a valid username and password.
     *
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \Exception
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __invoke(LoginCheckpointRequest $request): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
        }

        $details = $request->session()->get('auth_confirmation_token');
        if (!$this->hasValidSessionData($details)) {
            $this->sendFailedLoginResponse($request, null, self::TOKEN_EXPIRED_MESSAGE);
        }

        if (!hash_equals($request->input('confirmation_token') ?? '', $details['token_value'])) {
            $this->sendFailedLoginResponse($request);
        }

        try {
            /** @var \Pterodactyl\Models\User $user */
            $user = User::query()->findOrFail($details['user_id']);
        } catch (ModelNotFoundException) {
            $this->sendFailedLoginResponse($request, null, self::TOKEN_EXPIRED_MESSAGE);
        }

        // Recovery tokens go through a slightly different pathway for usage.
        if (!is_null($recoveryToken = $request->input('recovery_token'))) {
            if ($this->isValidRecoveryToken($user, $recoveryToken)) {
                Event::dispatch(new ProvidedAuthenticationToken($user, true));

                return $this->sendLoginResponse($user, $request);
            }
        } else {
            $decrypted = $this->encrypter->decrypt($user->totp_secret);

            if ($this->google2FA->verifyKey($decrypted, (string) $request->input('authentication_code') ?? '', config('pterodactyl.auth.2fa.window'))) {
                Event::dispatch(new ProvidedAuthenticationToken($user));

                return $this->sendLoginResponse($user, $request);
            }
        }

        $this->sendFailedLoginResponse($request, $user, !empty($recoveryToken) ? 'The recovery token provided is not valid.' : null);
    }

    /**
     * Determines if a given recovery token is valid for the user account. If we find a matching token
     * it will be deleted from the database.
     *
     * @throws \Exception
     */
    protected function isValidRecoveryToken(User $user, string $value): bool
    {
        foreach ($user->recoveryTokens as $token) {
            if (password_verify($value, $token->token)) {
                $token->delete();

                return true;
            }
        }

        return false;
    }

    /**
     * Determines if the data provided from the session is valid or not. This
     * will return false if the data is invalid, or if more time has passed than
     * was configured when the session was written.
     */
    protected function hasValidSessionData(array $data): bool
    {
        $validator = $this->validation->make($data, [
            'user_id' => 'required|integer|min:1',
            'token_value' => 'required|string',
            'expires_at' => 'required',
        ]);

        if ($validator->fails()) {
            return false;
        }

        if (!$data['expires_at'] instanceof CarbonInterface) {
            return false;
        }

        if ($data['expires_at']->isBefore(CarbonImmutable::now())) {
            return false;
        }

        return true;
    }
}
