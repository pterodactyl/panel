<?php

declare(strict_types=1);

namespace Pterodactyl\Events\Extensions;

use Pterodactyl\Events\Event;
use Throwable;

class ExtensionLoadFailed extends Event
{
    public function __construct(
        public string $identifier,
        public string $phase,
        public string $reason,
        public ?Throwable $exception = null,
    ) {}
}
