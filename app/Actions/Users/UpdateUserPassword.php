<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Users\UpdatesUserPasswords;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\User;

final readonly class UpdateUserPassword implements UpdatesUserPasswords
{
    public function __construct(private UpdatesUsers $users) {}

    /**
     * Changing a password, through the account page or a reset link, also ends every other
     * session and "remember me" login made with the old one.
     */
    public function update(User $user, string $password): User
    {
        $user = $this->users->update($user, ['password' => $password]);
        $user->setRememberToken(Str::random(60));
        $user->save();

        Activity::event('user:account.password-changed')->subject($user)->log();

        // Users changing their own password keep this session. A reset link has no session
        // to keep, and is never signed in here: an account with two-factor authentication
        // still has to pass its checkpoint.
        $guard = Auth::guard(Auth::getDefaultDriver());
        $current = $guard->user();
        if ($current instanceof User && $current->is($user)) {
            $guard->setUser($user);

            if (method_exists($guard, 'logoutOtherDevices')) {
                $guard->logoutOtherDevices($password);
            }
        }

        return $user;
    }
}
