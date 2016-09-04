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
use Settings;
use Hash;
use Validator;
use Mail;
use Carbon;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;
use Pterodactyl\Notifications\AccountCreated;

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
     * @param  string|null $password An unhashed version of the user's password.
     * @return bool|integer
     */
    public function create($email, $password = null, $admin = false)
    {
        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'root_admin' => $admin
        ], [
            'email' => 'required|email|unique:users,email',
            'password' => 'nullable|regex:((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})',
            'root_admin' => 'required|boolean'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();

        try {
            $user = new Models\User;
            $uuid = new UuidService;

            $user->uuid = $uuid->generate('users', 'uuid');
            $user->email = $email;
            $user->password = Hash::make((is_null($password)) ? str_random(30) : $password);
            $user->language = 'en';
            $user->root_admin = ($admin) ? 1 : 0;
            $user->save();

            // Setup a Password Reset to use when they set a password.
            $token = str_random(32);
            DB::table('password_resets')->insert([
                'email' => $user->email,
                'token' => $token,
                'created_at' => Carbon::now()->toDateTimeString()
            ]);

            $user->notify((new AccountCreated($token)));

            DB::commit();
            return $user->id;
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
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
        $user = Models\User::findOrFail($id);

        $validator = Validator::make($data, [
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|regex:((?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,})',
            'root_admin' => 'sometimes|required|boolean',
            'language' => 'sometimes|required|string|min:1|max:5',
            'use_totp' => 'sometimes|required|boolean',
            'totp_secret' => 'sometimes|required|size:16'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        if(array_key_exists('password', $data)) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['password_confirmation'])) {
            unset($data['password_confirmation']);
        }

        $user->fill($data);
        $user->save();
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

        try {
            Models\Permission::where('user_id', $id)->delete();
            Models\Subuser::where('user_id', $id)->delete();
            Models\User::destroy($id);

            DB::commit();
            return true;
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

}
