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

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use PragmaRX\Google2FA\Contracts\Google2FA;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Models\User;

class TwoFactorSetupService
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \PragmaRX\Google2FA\Contracts\Google2FA
     */
    protected $google2FA;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * TwoFactorSetupService constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \PragmaRX\Google2FA\Contracts\Google2FA                   $google2FA
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        ConfigRepository $config,
        Google2FA $google2FA,
        UserRepositoryInterface $repository
    ) {
        $this->config = $config;
        $this->google2FA = $google2FA;
        $this->repository = $repository;
    }

    /**
     * Generate a 2FA token and store it in the database.
     *
     * @param int|\Pterodactyl\Models\User $user
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($user)
    {
        if (! $user instanceof User) {
            $user = $this->repository->find($user);
        }

        $secret = $this->google2FA->generateSecretKey();
        $image = $this->google2FA->getQRCodeGoogleUrl($this->config->get('app.name'), $user->email, $secret);

        $this->repository->withoutFresh()->update($user->id, ['totp_secret' => $secret]);

        return [
            'qrImage' => $image,
            'secret' => $secret,
        ];
    }
}
