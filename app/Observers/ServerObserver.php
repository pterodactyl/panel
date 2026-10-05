<?php

declare(strict_types=1);

namespace Pterodactyl\Observers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Pterodactyl\Events\Server\Created;
use Pterodactyl\Events\Server\Creating;
use Pterodactyl\Events\Server\Deleted;
use Pterodactyl\Events\Server\Deleting;
use Pterodactyl\Events\Server\Saved;
use Pterodactyl\Events\Server\Saving;
use Pterodactyl\Events\Server\Updated;
use Pterodactyl\Events\Server\Updating;
use Pterodactyl\Models\Server;

class ServerObserver
{
    use DispatchesJobs;

    /**
     * Listen to the Server creating event.
     */
    public function creating(Server $server): void
    {
        event(new Creating($server));
    }

    /**
     * Listen to the Server created event.
     */
    public function created(Server $server): void
    {
        event(new Created($server));
    }

    /**
     * Listen to the Server deleting event.
     */
    public function deleting(Server $server): void
    {
        event(new Deleting($server));
    }

    /**
     * Listen to the Server deleted event.
     */
    public function deleted(Server $server): void
    {
        event(new Deleted($server));
    }

    /**
     * Listen to the Server saving event.
     */
    public function saving(Server $server): void
    {
        event(new Saving($server));
    }

    /**
     * Listen to the Server saved event.
     */
    public function saved(Server $server): void
    {
        event(new Saved($server));
    }

    /**
     * Listen to the Server updating event.
     */
    public function updating(Server $server): void
    {
        event(new Updating($server));
    }

    public function updated(Server $server): void
    {
        event(new Updated($server));
    }
}
