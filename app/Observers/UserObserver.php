<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Observers;

use Pterodactyl\Events;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Components\UuidService;

class UserObserver
{
    protected $uuid;

    public function __construct(UuidService $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Listen to the User creating event.
     *
     * @param \Pterodactyl\Models\User $user
     */
    public function creating(User $user)
    {
        $user->uuid = $this->uuid->generate('users', 'uuid');

        event(new Events\User\Creating($user));
    }

    /**
     * Listen to the User created event.
     *
     * @param \Pterodactyl\Models\User $user
     */
    public function created(User $user)
    {
        event(new Events\User\Created($user));
    }

    /**
     * Listen to the User deleting event.
     *
     * @param \Pterodactyl\Models\User $user
     */
    public function deleting(User $user)
    {
        event(new Events\User\Deleting($user));
    }

    /**
     * Listen to the User deleted event.
     *
     * @param \Pterodactyl\Models\User $user
     */
    public function deleted(User $user)
    {
        event(new Events\User\Deleted($user));
    }
}
