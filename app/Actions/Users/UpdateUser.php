<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Arr;
use Pterodactyl\Contracts\Users\UpdatesUsers;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Models\User;
use Throwable;

final class UpdateUser implements UpdatesUsers
{
    /**
     * Update the user model instance and return the updated model. The hashed
     * password cast on the model takes care of hashing a new password.
     *
     * @param  ModelAttributes  $data
     *
     * @throws Throwable
     */
    public function update(User $user, array $data): User
    {
        if (empty(Arr::get($data, 'password'))) {
            unset($data['password']);
        }

        $user->forceFill($data)->saveOrFail();

        if (isset($data['password'])) {
            event(new PasswordChanged($user));
        }

        return $user->refresh();
    }
}
