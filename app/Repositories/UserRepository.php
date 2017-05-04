<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>
 * Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>.
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
use Auth;
use Hash;
use Settings;
use Validator;
use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\DisplayValidationException;

class UserRepository
{
    /**
     * Creates a user on the panel. Returns the created user's ID.
     *
     * @param  array  $data
     * @return \Pterodactyl\Models\User
     *
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|between:1,255|unique:users,username|' . Models\User::USERNAME_RULES,
            'name_first' => 'required|string|between:1,255',
            'name_last' => 'required|string|between:1,255',
            'password' => 'sometimes|nullable|' . Models\User::PASSWORD_RULES,
            'root_admin' => 'required|boolean',
            'custom_id' => 'sometimes|nullable|unique:users,id',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        DB::beginTransaction();

        try {
            $user = new Models\User;
            $uuid = new UuidService;

            // Support for API Services
            if (isset($data['custom_id']) && ! is_null($data['custom_id'])) {
                $user->id = $token;
            }

            // UUIDs are not mass-fillable.
            $user->uuid = $uuid->generate('users', 'uuid');

            $user->fill([
                'email' => $data['email'],
                'username' => $data['username'],
                'name_first' => $data['name_first'],
                'name_last' => $data['name_last'],
                'password' => (empty($data['password'])) ? 'unset' : Hash::make($data['password']),
                'root_admin' => $data['root_admin'],
                'language' => Settings::get('default_language', 'en'),
            ]);
            $user->save();

            DB::commit();

            return $user;
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Updates a user on the panel.
     *
     * @param  int    $id
     * @param  array  $data
     * @return \Pterodactyl\Models\User
     *
     * @throws \Pterodactyl\Exceptions\DisplayValidationException
     */
    public function update($id, array $data)
    {
        $user = Models\User::findOrFail($id);

        $validator = Validator::make($data, [
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'username' => 'sometimes|required|string|between:1,255|unique:users,username,' . $user->id . '|' . Models\User::USERNAME_RULES,
            'name_first' => 'sometimes|required|string|between:1,255',
            'name_last' => 'sometimes|required|string|between:1,255',
            'password' => 'sometimes|nullable|' . Models\User::PASSWORD_RULES,
            'root_admin' => 'sometimes|required|boolean',
            'language' => 'sometimes|required|string|min:1|max:5',
            'use_totp' => 'sometimes|required|boolean',
            'totp_secret' => 'sometimes|required|size:16',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException(json_encode($validator->errors()));
        }

        // The password and root_admin fields are not mass assignable.
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->fill($data)->save();

        return $user;
    }

    /**
     * Deletes a user on the panel.
     *
     * @param  int $id
     * @return void
     * @todo   Move user self-deletion checking to the controller, rather than the repository.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete($id)
    {
        if (Models\Server::where('owner_id', $id)->count() > 0) {
            throw new DisplayException('Cannot delete a user with active servers attached to thier account.');
        }

        if (! is_null(Auth::user()) && (int) Auth::user()->id === (int) $id) {
            throw new DisplayException('Cannot delete your own account.');
        }

        DB::beginTransaction();

        try {
            foreach (Models\Subuser::with('permissions')->where('user_id', $id)->get() as &$subuser) {
                foreach ($subuser->permissions as &$permission) {
                    $permission->delete();
                }

                $subuser->delete();
            }

            Models\User::destroy($id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
