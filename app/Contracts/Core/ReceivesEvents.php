<?php

namespace Pterodactyl\Contracts\Core;

use Pterodactyl\Events\Event;

interface ReceivesEvents
{
    /**
     * Handles receiving an event from the application.
     */
    public function handle(Event $notification): void;
}
