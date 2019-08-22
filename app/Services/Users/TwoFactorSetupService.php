<?php

namespace App\Services\Users;

use Exception;
use App\Models\User;
use RuntimeException;
use Illuminate\Contracts\Encryption\Encrypter;
use App\Contracts\Repository\UserRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class TwoFactorSetupService
{
    const VALID_BASE32_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \App\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * TwoFactorSetupService constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                   $config
     * @param \Illuminate\Contracts\Encryption\Encrypter                $encrypter
     * @param \App\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        ConfigRepository $config,
        Encrypter $encrypter,
        UserRepositoryInterface $repository
    ) {
        $this->config = $config;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Generate a 2FA token and store it in the database before returning the
     * QR code URL. This URL will need to be attached to a QR generating service in
     * order to function.
     *
     * @param \App\Models\User $user
     * @return string
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(User $user): string
    {
        $secret = '';
        try {
            for ($i = 0; $i < $this->config->get('pterodactyl.auth.2fa.bytes', 16); $i++) {
                $secret .= substr(self::VALID_BASE32_CHARACTERS, random_int(0, 31), 1);
            }
        } catch (Exception $exception) {
            throw new RuntimeException($exception->getMessage(), 0, $exception);
        }

        $this->repository->withoutFreshModel()->update($user->id, [
            'totp_secret' => $this->encrypter->encrypt($secret),
        ]);

        $company = preg_replace('/\s/', '', $this->config->get('app.name'));

        return sprintf(
            'otpauth://totp/%1$s:%2$s?secret=%3$s&issuer=%1$s',
            rawurlencode($company),
            rawurlencode($user->email),
            rawurlencode($secret)
        );
    }
}
