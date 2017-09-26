<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

interface SessionRepositoryInterface extends RepositoryInterface
{
    /**
     * Delete a session for a given user.
     *
     * @param int $user
     * @param int $session
     * @return null|int
     */
    public function deleteUserSession($user, $session);

    /**
     * Return all of the active sessions for a user.
     *
     * @param int $user
     * @return \Illuminate\Support\Collection
     */
    public function getUserSessions($user);
}
