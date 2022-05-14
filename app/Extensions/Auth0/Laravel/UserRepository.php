<?php

namespace Pterodactyl\Extensions\Auth0\Laravel;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Auth0\Laravel\Contract\Auth\User\Repository as Auth0Repository;

class UserRepository implements Auth0Repository
{
    /**
     * Authenticate the user from a session on Auth0. If there is no account present on the
     * system currently, create them.
     *
     * @param array $user
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function fromSession(array $user): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        $match = User::query()->where('email', $user['email'])->first();
        if (is_null($match)) {
            $match = User::query()->forceCreate([
                'uuid' => Uuid::uuid4()->toString(),
                'email' => $user['email'],
                'username' => 'auth0.' . $user['nickname'],
                'name_first' => 'Auth0',
                'name_last' => 'User',
                'password' => password_hash(Str::random(32), PASSWORD_DEFAULT),
            ]);
        }

        return $match;
    }

    public function fromAccessToken(array $user): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        dd('from token', $user);
    }
}
