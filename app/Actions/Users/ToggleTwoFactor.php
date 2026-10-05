<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use Pterodactyl\Contracts\Users\TogglesTwoFactor;
use Pterodactyl\Exceptions\Service\User\TwoFactorAuthenticationTokenInvalid;
use Pterodactyl\Models\RecoveryToken;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

final readonly class ToggleTwoFactor implements TogglesTwoFactor
{
    public function __construct(private Google2FA $google2FA) {}

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
    public function toggle(User $user, string $token, ?bool $toggleState = null): array
    {
        $encryptedSecret = $user->totp_secret;
        throw_if($encryptedSecret === null, TwoFactorAuthenticationTokenInvalid::class);

        $secret = JsonValueGuard::string(Crypt::decrypt($encryptedSecret));

        $isValidToken = $this->google2FA->verifyKey($secret, $token, JsonValueGuard::integer(config()->get('pterodactyl.auth.2fa.window')));

        throw_unless($isValidToken, TwoFactorAuthenticationTokenInvalid::class);

        return DB::transaction(function () use ($user, $toggleState): array {
            // Now that we're enabling 2FA on the account, generate 10 recovery tokens for the account
            // and store them hashed in the database. We'll return them to the caller so that the user
            // can see and save them.
            //
            // If a user is unable to login with a 2FA token they can provide one of these backup codes
            // which will then be marked as deleted from the database and will also bypass 2FA protections
            // on their account.
            $tokens = [];
            if ((! $toggleState && ! $user->use_totp) || $toggleState) {
                $inserts = [];
                for ($i = 0; $i < 10; $i++) {
                    $token = Str::random(10);

                    $inserts[] = [
                        'user_id' => $user->id,
                        'token' => Hash::make($token),
                        // insert() won't actually set the time on the models, so make sure we do this
                        // manually here.
                        'created_at' => now(),
                    ];

                    $tokens[] = $token;
                }

                // Before inserting any new records make sure all of the old ones are deleted to avoid
                // any issues or storing an unnecessary number of tokens in the database.
                $user->recoveryTokens()->delete();

                // Bulk insert the hashed tokens.
                RecoveryToken::query()->insert($inserts);
            }

            $user->update([
                'totp_authenticated_at' => null,
                'use_totp' => ($toggleState ?? ! $user->use_totp),
            ]);

            return $tokens;
        });
    }
}
