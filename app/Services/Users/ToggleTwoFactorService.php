<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Users;

use Pterodactyl\Models\User;
use PragmaRX\Google2FA\Contracts\Google2FA;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid;

class ToggleTwoFactorService
{
    /**
     * @var \PragmaRX\Google2FA\Contracts\Google2FA
     */
    protected $google2FA;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * ToggleTwoFactorService constructor.
     *
     * @param \PragmaRX\Google2FA\Contracts\Google2FA                   $google2FA
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        Google2FA $google2FA,
        UserRepositoryInterface $repository
    ) {
        $this->google2FA = $google2FA;
        $this->repository = $repository;
    }

    /**
     * @param int|\Pterodactyl\Models\User $user
     * @param string                       $token
     * @param null|bool                    $toggleState
     * @return bool
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid
     */
    public function handle($user, $token, $toggleState = null)
    {
        if (! $user instanceof User) {
            $user = $this->repository->find($user);
        }

        if (! $this->google2FA->verifyKey($user->totp_secret, $token, 2)) {
            throw new TwoFactorAuthenticationTokenInvalid;
        }

        $this->repository->withoutFresh()->update($user->id, [
            'use_totp' => (is_null($toggleState) ? ! $user->use_totp : $toggleState),
        ]);

        return true;
    }
}
