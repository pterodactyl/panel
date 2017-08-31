<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Services\Users;

use PragmaRX\Google2FA\Contracts\Google2FA;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid;
use Pterodactyl\Models\User;

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
