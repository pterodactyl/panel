<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;

interface SessionRepositoryInterface extends RepositoryInterface
{
    /**
     * Return all of the active sessions for a user.
     *
     * @param int $user
     * @return \Illuminate\Support\Collection
     */
    public function getUserSessions(int $user): Collection;

    /**
     * Delete a session for a given user.
     *
     * @param int    $user
     * @param string $session
     * @return null|int
     */
    public function deleteUserSession(int $user, string $session);
}
