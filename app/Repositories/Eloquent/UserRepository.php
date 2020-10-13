<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\User;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return User::class;
    }
}
