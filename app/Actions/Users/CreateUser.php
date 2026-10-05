<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Users;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Users\CreatesUsers;
use Pterodactyl\Models\User;
use Pterodactyl\Notifications\AccountCreated;
use Throwable;

final readonly class CreateUser implements CreatesUsers
{
    /**
     * Create a new user on the system. When no password is provided a random one is
     * set and a password reset token is generated for the account welcome email.
     *
     * The UUID and password hash are handled by the model itself (HasVersion4Uuids
     * and the hashed password cast).
     *
     * @param  ModelAttributes  $data
     *
     * @throws Throwable
     */
    public function create(array $data): User
    {
        $generateResetToken = empty($data['password']);
        if ($generateResetToken) {
            $data['password'] = Str::random(30);
        }

        [$user, $token] = DB::transaction(function () use ($data, $generateResetToken): array {
            $user = User::query()->create($data);

            return [$user->refresh(), $generateResetToken ? Password::createToken($user) : null];
        });

        $user->notify(new AccountCreated($user, $token));

        return $user;
    }
}
