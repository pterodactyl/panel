<?php

namespace Pterodactyl\Services\Users;

use Ramsey\Uuid\Uuid;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Services\Helpers\TemporaryPasswordService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    private $hasher;

    /**
     * @var \Pterodactyl\Services\Helpers\TemporaryPasswordService
     */
    private $passwordService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * CreationService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                  $connection
     * @param \Illuminate\Contracts\Hashing\Hasher                      $hasher
     * @param \Pterodactyl\Services\Helpers\TemporaryPasswordService    $passwordService
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        ConnectionInterface $connection,
        Hasher $hasher,
        TemporaryPasswordService $passwordService,
        UserRepositoryInterface $repository
    ) {
        $this->connection = $connection;
        $this->hasher = $hasher;
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

        /** @var \Pterodactyl\Models\User $user */
        $user = $this->repository->create(array_merge($data, [
            'uuid' => Uuid::uuid4()->toString(),
        ]), true, true);

        $this->connection->commit();
        $user->notify(new AccountCreated($user, $token ?? null));

        return $user;
    }
}
