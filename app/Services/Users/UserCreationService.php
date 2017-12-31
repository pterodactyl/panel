<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Users;

use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Notifications\ChannelManager;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Services\Helpers\TemporaryPasswordService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserCreationService
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

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
     * CreationService constructor.
     *
     * @param \Illuminate\Foundation\Application                        $application
     * @param \Illuminate\Notifications\ChannelManager                  $notification
     * @param \Illuminate\Database\ConnectionInterface                  $connection
     * @param \Illuminate\Contracts\Hashing\Hasher                      $hasher
     * @param \Pterodactyl\Services\Helpers\TemporaryPasswordService    $passwordService
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        Application $application,
        ChannelManager $notification,
        ConnectionInterface $connection,
        Hasher $hasher,
        TemporaryPasswordService $passwordService,
        UserRepositoryInterface $repository
    ) {
        $this->app = $application;
        $this->connection = $connection;
        $this->hasher = $hasher;
        $this->notification = $notification;
        $this->passwordService = $passwordService;
        $this->repository = $repository;
    }

    /**
     * Create a new user on the system.
     *
     * @param array $data
     * @return \Pterodactyl\Models\User
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        if (array_key_exists('password', $data) && ! empty($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        $this->connection->beginTransaction();
        if (! isset($data['password']) || empty($data['password'])) {
            $data['password'] = $this->hasher->make(str_random(30));
            $token = $this->passwordService->handle($data['email']);
        }

        $user = $this->repository->create(array_merge($data, [
            'uuid' => Uuid::uuid4()->toString(),
        ]));

        $this->connection->commit();

        // @todo fire event, handle notification there
        $this->notification->send($user, $this->app->makeWith(AccountCreated::class, [
            'user' => [
                'name' => $user->name_first,
                'username' => $user->username,
                'token' => $token ?? null,
            ],
        ]));

        return $user;
    }
}
