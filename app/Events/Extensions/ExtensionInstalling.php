<?php

declare(strict_types=1);

namespace Pterodactyl\Events\Extensions;

use Pterodactyl\Events\Event;
use Pterodactyl\Services\Extensions\ExtensionManifest;

class ExtensionInstalling extends Event
{
    public function __construct(
        public ExtensionManifest $manifest,
        public string $source,
    ) {}
}
