<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
