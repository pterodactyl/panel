<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Extensions;

use Pterodactyl\Services\Extensions\ExtensionManifest;

interface InstallsExtensions
{
    public function install(string $source, bool $enable = false): ExtensionManifest;
}
