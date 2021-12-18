<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class LoginCheckpointController extends AbstractLoginController
{
    private CacheRepository $cache;
    private Encrypter $encrypter;
    private Google2FA $google2FA;


    /**
     * LoginCheckpointController constructor.
     */
    public function __construct(
        AuthManager $auth,
        Repository $config,
        CacheRepository $cache,
        Encrypter $encrypter,
        Google2FA $google2FA
    ) {
        parent::__construct($auth, $config);

        $this->cache = $cache;
        $this->encrypter = $encrypter;
        $this->google2FA = $google2FA;
    }

    /**
     * Handle a login where the user is required to provide a TOTP authentication
     * token. Once a user has reached this stage it is assumed that they have already
     * provided a valid username and password.
     *
     * @return \Illuminate\Http\JsonResponse|void
     *
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __invoke(LoginCheckpointRequest $request): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
            return;
        }

        $token = $request->input('confirmation_token');
        try {
            /** @var \Pterodactyl\Models\User $user */
            $user = User::query()->findOrFail($this->cache->get($token, 0));
        } catch (ModelNotFoundException $exception) {
            $this->incrementLoginAttempts($request);

            $this->sendFailedLoginResponse(
                $request,
                null,
                'The authentication token provided has expired, please refresh the page and try again.'
            );
            return;
        }

        // Recovery tokens go through a slightly different pathway for usage.
        if (!is_null($recoveryToken = $request->input('recovery_token'))) {
            if ($this->isValidRecoveryToken($user, $recoveryToken)) {
                return $this->sendLoginResponse($user, $request);
            }
        } else {
            $decrypted = $this->encrypter->decrypt($user->totp_secret);

            if ($this->google2FA->verifyKey($decrypted, (string) $request->input('authentication_code') ?? '', config('pterodactyl.auth.2fa.window'))) {
                $this->cache->delete($token);

                return $this->sendLoginResponse($user, $request);
            }
        }

        $this->incrementLoginAttempts($request);
        $this->sendFailedLoginResponse($request, $user, !empty($recoveryToken) ? 'The recovery token provided is not valid.' : null);
    }

    /**
     * Determines if a given recovery token is valid for the user account. If we find a matching token
     * it will be deleted from the database.
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function isValidRecoveryToken(User $user, string $value)
    {
        foreach ($user->recoveryTokens as $token) {
            if (password_verify($value, $token->token)) {
                $token->delete();

                return true;
            }
        }

        return false;
    }
}
