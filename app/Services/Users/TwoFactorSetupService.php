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
use Illuminate\Contracts\Config\Repository as ConfigRepository;

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
