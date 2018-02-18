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
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class TwoFactorSetupService
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
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * TwoFactorSetupService constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \Illuminate\Contracts\Encryption\Encrypter                $encrypter
     * @param \PragmaRX\Google2FA\Google2FA                             $google2FA
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        ConfigRepository $config,
        Encrypter $encrypter,
        Google2FA $google2FA,
        UserRepositoryInterface $repository
    ) {
        $this->config = $config;
        $this->encrypter = $encrypter;
        $this->google2FA = $google2FA;
        $this->repository = $repository;
    }

    /**
     * Generate a 2FA token and store it in the database before returning the
     * QR code image.
     *
     * @param \Pterodactyl\Models\User $user
     * @return string
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(User $user): string
    {
        $secret = $this->google2FA->generateSecretKey($this->config->get('pterodactyl.auth.2fa.bytes'));
        $image = $this->google2FA->getQRCodeGoogleUrl($this->config->get('app.name'), $user->email, $secret);

        $this->repository->withoutFreshModel()->update($user->id, [
            'totp_secret' => $this->encrypter->encrypt($secret),
        ]);

        return $image;
    }
}
