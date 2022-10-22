<?php

namespace Pterodactyl\Services\Users;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Auth\PasswordBroker;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Exceptions\Model\DataValidationException;

class UserCreationService
{
    /**
     * UserCreationService constructor.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private Hasher $hasher,
        private PasswordBroker $passwordBroker
    ) {
    }

    /**
     * Create a new user on the system.
     *
     * @throws \Exception
     * @throws DataValidationException
     */
    public function handle(array $data): User
    {
        if (array_key_exists('password', $data) && !empty($data['password'])) {
            $data['password'] = $this->hasher->make($data['password']);
        }

        $this->connection->beginTransaction();
        if (empty($data['password'])) {
            $generateResetToken = true;
            $data['password'] = $this->hasher->make(str_random(30));
        }

        // /** @var User $user */
        // $user = $this->repository->create($data, true, true);

        $user = User::query()->forceCreate(array_merge($data, [
            'uuid' => Uuid::uuid4()->toString(),
        ]));

        if (isset($generateResetToken)) {
            $token = $this->passwordBroker->createToken($user);
        }

        $this->connection->commit();
        $user->notify(new AccountCreated($user, $token ?? null));

        return $user;
    }
}
