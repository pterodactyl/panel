<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
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

namespace Pterodactyl\Services;

use Pterodactyl\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Services\Helpers\TemporaryPasswordService;

class UserService
{
    /**
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * @var \Pterodactyl\Services\Helpers\TemporaryPasswordService
     */
    protected $passwordService;

    /**
     * @var \Pterodactyl\Models\User
     */
    protected $model;

    /**
     * UserService constructor.
     *
     * @param  \Illuminate\Database\Connection $database
     * @param  \Illuminate\Contracts\Hashing\Hasher                                $hasher
     * @param  \Pterodactyl\Services\Helpers\TemporaryPasswordService               $passwordService
     * @param  \Pterodactyl\Models\User                                            $model
     */
    public function __construct(
        Connection $database,
        Hasher $hasher,
        TemporaryPasswordService $passwordService,
        User $model
    ) {
        $this->database = $database;
        $this->hasher = $hasher;
        $this->passwordService = $passwordService;
        $this->model = $model;
    }

    /**
     * Create a new user on the system.
     *
     * @param  array $data
     * @return \Pterodactyl\Models\User
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function create(array $data)
    {
        if (array_key_exists('password', $data) && ! empty($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        $user = $this->model->newInstance($data);

        // Persist the data
        $token = $this->database->transaction(function () use ($user) {
            if (empty($user->password)) {
                $user->password = $this->hasher->make(str_random(30));
                $token = $this->passwordService->generateReset($user->email);
            }

            if (! $user->save()) {
                throw new DataValidationException($user->getValidator());
            }

            return $token ?? null;
        });

        $user->notify(new AccountCreated([
            'name' => $user->name_first,
            'username' => $user->username,
            'token' => $token,
        ]));

        return $user;
    }

    /**
     * Update the user model.
     *
     * @param  int|\Pterodactyl\Models\User  $user
     * @param  array                     $data
     * @return \Pterodactyl\Models\User
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function update($user, array $data)
    {
        if (! $user instanceof User) {
            $user = $this->model->findOrFail($user);
        }

        if (isset($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        $user->fill($data);

        if (! $user->save()) {
            throw new DataValidationException($user->getValidator());
        }

        return $user;
    }

    /**
     * @param  int|\Pterodactyl\Models\User $user
     * @return bool|null
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete($user)
    {
        if (! $user instanceof User) {
            $user = $this->model->findOrFail($user);
        }

        if ($user->servers()->count() > 0) {
            throw new DisplayException('Cannot delete an account that has active servers attached to it.');
        }

        return $user->delete();
    }
}
