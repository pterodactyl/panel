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
     * LoginCheckpointController constructor.
     *
     * @param \Illuminate\Auth\AuthManager                              $auth
     * @param \Illuminate\Contracts\Encryption\Encrypter                $encrypter
     * @param \PragmaRX\Google2FA\Google2FA                             $google2FA
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \Illuminate\Contracts\Cache\Repository                    $cache
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        AuthManager $auth,
        Encrypter $encrypter,
        Google2FA $google2FA,
        Repository $config,
        CacheRepository $cache,
        UserRepositoryInterface $repository
    ) {
        parent::__construct($auth, $config);

        $this->google2FA = $google2FA;
        $this->cache = $cache;
        $this->repository = $repository;
        $this->encrypter = $encrypter;
    }

    /**
     * Handle a login where the user is required to provide a TOTP authentication
     * token. Once a user has reached this stage it is assumed that they have already
     * provided a valid username and password.
     *
     * @param \Pterodactyl\Http\Requests\Auth\LoginCheckpointRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException
     * @throws \PragmaRX\Google2FA\Exceptions\InvalidCharactersException
     * @throws \PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function __invoke(LoginCheckpointRequest $request): JsonResponse
    {
        try {
            $user = $this->repository->find(
                $this->cache->pull($request->input('confirmation_token'), 0)
            );
        } catch (RecordNotFoundException $exception) {
            return $this->sendFailedLoginResponse($request);
        }

        $decrypted = $this->encrypter->decrypt($user->totp_secret);
        $window = $this->config->get('pterodactyl.auth.2fa.window');

        if ($this->google2FA->verifyKey($decrypted, $request->input('authentication_code'), $window)) {
            return $this->sendLoginResponse($user, $request);
        }

        return $this->sendFailedLoginResponse($request, $user);
    }
}
