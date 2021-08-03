<?php

namespace Pterodactyl\Observers;

use Pterodactyl\Events;
use Pterodactyl\Models\User;

class UserObserver
{
    protected $uuid;

    /**
     * Listen to the User creating event.
     */
    public function creating(User $user)
    {
        event(new Events\User\Creating($user));
    }

    /**
     * Listen to the User created event.
     */
    public function created(User $user)
    {
        event(new Events\User\Created($user));
    }

    /**
     * Listen to the User deleting event.
     */
    public function deleting(User $user)
    {
        event(new Events\User\Deleting($user));
    }

    /**
     * Listen to the User deleted event.
     */
    public function deleted(User $user)
    {
        event(new Events\User\Deleted($user));
    }
}
