<?php

namespace Pterodactyl\Repositories;

use Hash;

use Pterodactyl\Models\User;
use Pterodactyl\Services\UuidService;

class UserRepository
{

    public function __construct()
    {
        //
    }

    /**
     * Creates a user on the panel. Returns the created user's ID.
     *
     * @param  string $username
     * @param  string $email
     * @param  string $password An unhashed version of the user's password.
     * @return integer
     */
    public function create($username, $email, $password)
    {

        $user = new User;
        $uuid = new UuidService;

        $user->uuid = $uuid->generate('users', 'uuid');

        $user->username = $username;
        $user->email = $email;
        $user->password = Hash::make($password);

        $user->save();

        return $user->id;

    }

}
