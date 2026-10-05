<?php

declare(strict_types=1);

namespace Pterodactyl\Events\Extensions;

use Pterodactyl\Events\Event;

class ExtensionRemoved extends Event
{
    public function __construct(public string $identifier) {}
}
