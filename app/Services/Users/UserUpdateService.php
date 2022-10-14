<?php

namespace Pterodactyl\Services\Users;

use Pterodactyl\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Traits\Services\HasUserLevels;

class UserUpdateService
{
    use HasUserLevels;

    /**
     * UserUpdateService constructor.
     */
    public function __construct(private Hasher $hasher)
    {
    }

    /**
     * Update the user model instance and return the updated model.
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $data): User
    {
        if (!empty(array_get($data, 'password'))) {
            $data['password'] = $this->hasher->make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->forceFill($data)->saveOrFail();

        return $user->refresh();
    }
}
