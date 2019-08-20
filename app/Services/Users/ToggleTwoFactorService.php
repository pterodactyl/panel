<?php

namespace App\Services\Users;

use Carbon\Carbon;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use App\Contracts\Repository\UserRepositoryInterface;
use App\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid;

class ToggleTwoFactorService
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \PragmaRX\Google2FA\Google2FA
     */
    private $google2FA;

    /**
     * @var \App\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * ToggleTwoFactorService constructor.
     *
     * @param \Illuminate\Contracts\Encryption\Encrypter                $encrypter
     * @param \PragmaRX\Google2FA\Google2FA                             $google2FA
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \App\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        Encrypter $encrypter,
        Google2FA $google2FA,
        Repository $config,
        UserRepositoryInterface $repository
    ) {
        $this->config = $config;
        $this->encrypter = $encrypter;
        $this->google2FA = $google2FA;
        $this->repository = $repository;
    }

    /**
     * Toggle 2FA on an account only if the token provided is valid.
     *
     * @param \App\Models\User $user
     * @param string                   $token
     * @param bool|null                $toggleState
     * @return bool
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \App\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid
     */
    public function handle(User $user, string $token, bool $toggleState = null): bool
    {
        $window = $this->config->get('pterodactyl.auth.2fa.window');
        $secret = $this->encrypter->decrypt($user->totp_secret);

        $isValidToken = $this->google2FA->verifyKey($secret, $token, $window);

        if (! $isValidToken) {
            throw new TwoFactorAuthenticationTokenInvalid;
        }

        $this->repository->withoutFreshModel()->update($user->id, [
            'totp_authenticated_at' => Carbon::now(),
            'use_totp' => (is_null($toggleState) ? ! $user->use_totp : $toggleState),
        ]);

        return true;
    }
}
