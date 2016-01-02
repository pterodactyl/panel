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
     * @param  string $email
     * @param  string $password An unhashed version of the user's password.
     * @return bool|integer
     */
    public function create($email, $password)
    {
        $user = new User;
        $uuid = new UuidService;

        $user->uuid = $uuid->generate('users', 'uuid');
        $user->email = $email;
        $user->password = Hash::make($password);

        return ($user->save()) ? $user->id : false;
    }

    /**
     * Updates a user on the panel.
     *
     * @param  integer $id
     * @param  array $user An array of columns and their associated values to update for the user.
     * @return boolean
     */
    public function update($id, array $user)
    {
        if(array_key_exists('password', $user)) {
            $user['password'] = Hash::make($user['password']);
        }

        return User::find($id)->update($user);
    }

    /**
     * Deletes a user on the panel, returns the number of records deleted.
     *
     * @param  integer $id
     * @return integer
     */
    public function delete($id)
    {
        return User::destroy($id);
    }

}
