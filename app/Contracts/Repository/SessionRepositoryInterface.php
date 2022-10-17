<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;

interface SessionRepositoryInterface extends RepositoryInterface
{
    /**
     * Return all the active sessions for a user.
     */
    public function getUserSessions(int $user): Collection;

    /**
     * Delete a session for a given user.
     */
    public function deleteUserSession(int $user, string $session): ?int;
}
