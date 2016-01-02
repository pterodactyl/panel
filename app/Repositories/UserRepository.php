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

    /**
     * Updates a user on the panel. Returns true if the update was successful.
     *
     * @param  string $username
     * @param  string $email
     * @param  string $password An unhashed version of the user's password.
     * @return boolean
     */
    public function update($id, $user)
    {
        if(array_key_exists('password', $user)) {
           $user['password'] = Hash::make($user['password']);
       }

        User::where('id', $id)->update($user);
        return true;
    }

    /**
     * Deletes a user on the panel. Returns true if the deletion was successful.
     *
     * @param  string $username
     * @param  string $email
     * @param  string $password An unhashed version of the user's password.
     * @return boolean
     */
    public function delete($id)
    {
        User::destroy($id);
        return true;
    }

}
