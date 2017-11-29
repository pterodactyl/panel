<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Session;
use Pterodactyl\Contracts\Repository\SessionRepositoryInterface;

class SessionRepository extends EloquentRepository implements SessionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return Session::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserSessions($user)
    {
        return $this->getBuilder()->where('user_id', $user)->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUserSession($user, $session)
    {
        return $this->getBuilder()->where('user_id', $user)->where('id', $session)->delete();
    }
}
