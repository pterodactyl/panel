<?php
/**
 * Pterodactyl Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Pterodactyl\Repositories;

use DB;
use Hash;
use Validator;
use Mail;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;

use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Exceptions\DisplayException;

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
    public function create($email, $password, $admin = false)
    {
        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'root_admin' => $admin
        ], [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|regex:((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})',
            'root_admin' => 'required|boolean'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        $user = new Models\User;
        $uuid = new UuidService;

        DB::beginTransaction();

        $user->uuid = $uuid->generate('users', 'uuid');
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->language = 'en';
        $user->root_admin = ($admin) ? 1 : 0;
        $user->save();

        try {
            Mail::queue('emails.new-account', [
                'email' => $user->email,
                'forgot' => route('auth.password'),
                'login' => route('auth.login')
            ], function ($message) use ($email) {
                $message->to($email);
                $message->subject('Pterodactyl - New Account');
            });
            DB::commit();
            return $user->id;
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Updates a user on the panel.
     *
     * @param  integer $id
     * @param  array $data An array of columns and their associated values to update for the user.
     * @return boolean
     */
    public function update($id, array $data)
    {
        $validator = Validator::make($data, [
            'email' => 'email|unique:users,email,' . $id,
            'password' => 'regex:((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})',
            'root_admin' => 'boolean',
            'language' => 'string|min:1|max:5',
            'use_totp' => 'boolean',
            'totp_secret' => 'size:16'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        if(array_key_exists('password', $data)) {
            $user['password'] = Hash::make($data['password']);
        }

        return Models\User::findOrFail($id)->update($data);
    }

    /**
     * Deletes a user on the panel, returns the number of records deleted.
     *
     * @param  integer $id
     * @return integer
     */
    public function delete($id)
    {
        if(Models\Server::where('owner', $id)->count() > 0) {
            throw new DisplayException('Cannot delete a user with active servers attached to thier account.');
        }

        DB::beginTransaction();

        Models\Permission::where('user_id', $id)->delete();
        Models\Subuser::where('user_id', $id)->delete();
        Models\User::destroy($id);

        try {
            DB::commit();
            return true;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

}
