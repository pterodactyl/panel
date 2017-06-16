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
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Services\Components\UuidService;
use Illuminate\Config\Repository as ConfigRepository;

class UserService
{
    const HMAC_ALGO = 'sha256';

    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * @var \Illuminate\Contracts\Auth\Guard
     */
    protected $guard;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * @var \Pterodactyl\Services\Components\UuidService
     */
    protected $uuid;

    /**
     * UserService constructor.
     *
     * @param  \Illuminate\Config\Repository                 $config
     * @param  \Illuminate\Database\Connection               $database
     * @param  \Illuminate\Contracts\Auth\Guard              $guard
     * @param  \Illuminate\Contracts\Hashing\Hasher          $hasher
     * @param  \Pterodactyl\Services\Components\UuidService  $uuid
     */
    public function __construct(
        ConfigRepository $config,
        Connection $database,
        Guard $guard,
        Hasher $hasher,
        UuidService $uuid
    ) {
        $this->config = $config;
        $this->database = $database;
        $this->guard = $guard;
        $this->hasher = $hasher;
        $this->uuid = $uuid;
    }

    /**
     * Assign a temporary password to an account and return an authentication token to
     * email to the user for resetting their password.
     *
     * @param  \Pterodactyl\Models\User  $user
     * @return string
     */
    protected function assignTemporaryPassword(User $user)
    {
        $user->password = $this->hasher->make(str_random(30));

        $token = hash_hmac(self::HMAC_ALGO, str_random(40), $this->config->get('app.key'));

        $this->database->table('password_resets')->insert([
            'email' => $user->email,
            'token' => $this->hasher->make($token),
        ]);

        return $token;
    }

    /**
     * Create a new user on the system.
     *
     * @param  array  $data
     * @return \Pterodactyl\Models\User
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function create(array $data)
    {
        if (array_key_exists('password', $data) && ! empty($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        $user = new User;
        $user->fill($data);

        // Persist the data
        $token = $this->database->transaction(function () use ($user) {
            if (empty($user->password)) {
                $token = $this->assignTemporaryPassword($user);
            }

            $user->save();

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
     * @param  \Pterodactyl\Models\User  $user
     * @param  array                     $data
     * @return \Pterodactyl\Models\User
     */
    public function update(User $user, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        $user->fill($data)->save();

        return $user;
    }

    /**
     * @param \Pterodactyl\Models\User $user
     * @return bool|null
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(User $user)
    {
        if ($user->servers()->count() > 0) {
            throw new DisplayException('Cannot delete an account that has active servers attached to it.');
        }

        if ($this->guard->check() && $this->guard->id() === $user->id) {
            throw new DisplayException('You cannot delete your own account.');
        }

        if ($user->servers()->count() > 0) {
            throw new DisplayException('Cannot delete an account that has active servers attached to it.');
        }

        return $user->delete();
    }
}
