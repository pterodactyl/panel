<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Users;

use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid;
use Pterodactyl\Models\User;
use Throwable;

interface TogglesTwoFactor
{
    /**
     * Toggle 2FA on an account only if the token provided is valid.
     *
     * @return list<string> The plaintext recovery tokens generated for the account,
     *                      empty when 2FA is being turned off.
     *
     * @throws Throwable
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws TwoFactorAuthenticationTokenInvalid
     */
    public function toggle(User $user, string $token, ?bool $toggleState = null): array;
}
