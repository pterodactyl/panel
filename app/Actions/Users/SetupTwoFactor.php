<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Exception;
use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Contracts\Users\SetsUpTwoFactor;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;
use RuntimeException;

final readonly class SetupTwoFactor implements SetsUpTwoFactor
{
    public const string VALID_BASE32_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a 2FA token and store it in the database before returning the
     * QR code URL. This URL will need to be attached to a QR generating service in
     * order to function.
     *
     * @return array{image_url_data: string, secret: string}
     */
    public function setup(User $user): array
    {
        $secret = '';
        try {
            for ($i = 0; $i < JsonValueGuard::integer(config('pterodactyl.auth.2fa.bytes', 16)); $i++) {
                $secret .= mb_substr(self::VALID_BASE32_CHARACTERS, random_int(0, 31), 1);
            }
        } catch (Exception $exception) {
            throw new RuntimeException($exception->getMessage(), 0, $exception);
        }

        $user->update(['totp_secret' => Crypt::encrypt($secret)]);

        $company = urlencode(preg_replace('/\s/', '', JsonValueGuard::string(config('app.name'))) ?? '');

        return [
            'image_url_data' => sprintf(
                'otpauth://totp/%1$s:%2$s?secret=%3$s&issuer=%1$s',
                rawurlencode($company),
                rawurlencode($user->email),
                rawurlencode($secret),
            ),
            'secret' => $secret,
        ];
    }
}
