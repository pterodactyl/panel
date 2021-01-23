<?php

namespace Pterodactyl\Services\Users;

use Pterodactyl\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Pterodactyl\Traits\Services\HasUserLevels;
use Pterodactyl\Repositories\Eloquent\UserRepository;

class UserUpdateService
{
    use HasUserLevels;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    private $hasher;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\UserRepository
     */
    private $repository;

    /**
     * UpdateService constructor.
     */
    public function __construct(Hasher $hasher, UserRepository $repository)
    {
        $this->hasher = $hasher;
        $this->repository = $repository;
    }

    /**
     * Update the user model instance.
     *
     * @return \Pterodactyl\Models\User
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(User $user, array $data)
    {
        if (!empty(array_get($data, 'password'))) {
            $data['password'] = $this->hasher->make($data['password']);
        } else {
            unset($data['password']);
        }

        /** @var \Pterodactyl\Models\User $response */
        $response = $this->repository->update($user->id, $data);

        return $response;
    }
}
