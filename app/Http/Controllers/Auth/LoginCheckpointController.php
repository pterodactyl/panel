<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Repositories\Eloquent\RecoveryTokenRepository;

class LoginCheckpointController extends AbstractLoginController
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * @var \PragmaRX\Google2FA\Google2FA
     */
    private $google2FA;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\RecoveryTokenRepository
     */
    private $recoveryTokenRepository;

    /**
     * LoginCheckpointController constructor.
     *
     * @param \Illuminate\Auth\AuthManager $auth
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     * @param \PragmaRX\Google2FA\Google2FA $google2FA
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param \Pterodactyl\Repositories\Eloquent\RecoveryTokenRepository $recoveryTokenRepository
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        AuthManager $auth,
        Encrypter $encrypter,
        Google2FA $google2FA,
        Repository $config,
        CacheRepository $cache,
        RecoveryTokenRepository $recoveryTokenRepository,
        UserRepositoryInterface $repository
    ) {
        parent::__construct($auth, $config);

        $this->google2FA = $google2FA;
        $this->cache = $cache;
        $this->repository = $repository;
        $this->encrypter = $encrypter;
        $this->recoveryTokenRepository = $recoveryTokenRepository;
    }

    /**
     * Handle a login where the user is required to provide a TOTP authentication
     * token. Once a user has reached this stage it is assumed that they have already
     * provided a valid username and password.
     *
     * @param \Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest $request
     * @return \Illuminate\Http\JsonResponse|void
     *
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function __invoke(LoginCheckpointRequest $request): JsonResponse
    {
        $token = $request->input('confirmation_token');
        $recoveryToken = $request->input('recovery_token');

        try {
            /** @var \Pterodactyl\Models\User $user */
            $user = $this->repository->find($this->cache->get($token, 0));
        } catch (RecordNotFoundException $exception) {
            return $this->sendFailedLoginResponse($request, null, 'The authentication token provided has expired, please refresh the page and try again.');
        }

        // If we got a recovery token try to find one that matches for the user and then continue
        // through the process (and delete the token).
        if (! is_null($recoveryToken)) {
            foreach ($user->recoveryTokens as $token) {
                if (password_verify($recoveryToken, $token->token)) {
                    $this->recoveryTokenRepository->delete($token->id);

                    return $this->sendLoginResponse($user, $request);
                }
            }
        } else {
            $decrypted = $this->encrypter->decrypt($user->totp_secret);

            if ($this->google2FA->verifyKey($decrypted, (string) $request->input('authentication_code') ?? '', config('pterodactyl.auth.2fa.window'))) {
                $this->cache->delete($token);

                return $this->sendLoginResponse($user, $request);
            }
        }

        return $this->sendFailedLoginResponse($request, $user, ! empty($recoveryToken) ? 'The recovery token provided is not valid.' : null);
    }
}
