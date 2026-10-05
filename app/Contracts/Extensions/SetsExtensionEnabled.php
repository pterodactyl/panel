<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Extensions;

use Pterodactyl\Services\Extensions\ExtensionManifest;

interface SetsExtensionEnabled
{
    public function setEnabled(string $identifier, bool $enabled, ?ExtensionManifest $manifest = null): void;
}
