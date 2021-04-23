<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Session;
use Illuminate\Support\Collection;
use Pterodactyl\Contracts\Repository\SessionRepositoryInterface;

class SessionRepository extends EloquentRepository implements SessionRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Session::class;
    }

    /**
     * Return all of the active sessions for a user.
     */
    public function getUserSessions(int $user): Collection
    {
        return $this->getBuilder()->where('user_id', $user)->get($this->getColumns());
    }

    /**
     * Delete a session for a given user.
     *
     * @return int|null
     */
    public function deleteUserSession(int $user, string $session)
    {
        return $this->getBuilder()->where('user_id', $user)->where('id', $session)->delete();
    }
}
