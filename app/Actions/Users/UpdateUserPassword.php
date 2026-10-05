<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Facades\Auth;
use Pterodactyl\Contracts\Users\UpdatesUserPasswords;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Models\User;

final readonly class UpdateUserPassword implements UpdatesUserPasswords
{
    public function __construct(private UpdatesUsers $users) {}

    public function update(User $user, string $password): User
    {
        $user = $this->users->update($user, ['password' => $password]);

        $guard = Auth::guard(Auth::getDefaultDriver());
        $guard->setUser($user);

        if (method_exists($guard, 'logoutOtherDevices')) {
            $guard->logoutOtherDevices($password);
        }

        return $user;
    }
}
