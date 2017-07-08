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

namespace Pterodactyl\Services\Administrative;

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Notifications\ChannelManager;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Services\Helpers\TemporaryPasswordService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserService
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * @var \Illuminate\Notifications\ChannelManager
     */
    protected $notification;

    /**
     * @var \Pterodactyl\Services\Helpers\TemporaryPasswordService
     */
    protected $passwordService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * UserService constructor.
     *
     * @param \Illuminate\Foundation\Application                         $application
     * @param  \Illuminate\Notifications\ChannelManager                  $notification
     * @param  \Illuminate\Database\ConnectionInterface                  $database
     * @param  \Illuminate\Contracts\Hashing\Hasher                      $hasher
     * @param  \Pterodactyl\Services\Helpers\TemporaryPasswordService    $passwordService
     * @param  \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        Application $application,
        ChannelManager $notification,
        ConnectionInterface $database,
        Hasher $hasher,
        TemporaryPasswordService $passwordService,
        UserRepositoryInterface $repository
    ) {
        $this->app = $application;
        $this->database = $database;
        $this->hasher = $hasher;
        $this->notification = $notification;
        $this->passwordService = $passwordService;
        $this->repository = $repository;
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

        // Begin Transaction
        $this->database->beginTransaction();

        if (! isset($data['password']) || empty($data['password'])) {
            $data['password'] = $this->hasher->make(str_random(30));
            $token = $this->passwordService->generateReset($data['email']);
        }

        $user = $this->repository->create($data);

        // Persist the data
        $this->database->commit();

        $this->notification->send($user, $this->app->makeWith(AccountCreated::class, [
            'user' => [
                'name' => $user->name_first,
                'username' => $user->username,
                'token' => $token ?? null,
            ],
        ]));

        return $user;
    }

    /**
     * Update the user model instance.
     *
     * @param  int   $id
     * @param  array $data
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function update($id, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        $user = $this->repository->update($id, $data);

        return $user;
    }
}
