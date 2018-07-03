<?php

namespace Pterodactyl\Contracts\Core;

use Pterodactyl\Events\Event;

interface ReceivesEvents
{
    /**
     * Handles receiving an event from the application.
     *
     * @param \Pterodactyl\Events\Event $notification
     */
    public function handle(Event $notification): void;
}
