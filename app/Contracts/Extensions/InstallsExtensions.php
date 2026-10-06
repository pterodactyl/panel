<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Extensions;

use Pterodactyl\Exceptions\Extensions\ExtensionAlreadyInstalledException;
use Pterodactyl\Services\Extensions\ExtensionManifest;

interface InstallsExtensions
{
    /**
     * Install a package. One whose id is already installed replaces that extension only
     * when $replace is set.
     *
     * @throws ExtensionAlreadyInstalledException
     */
    public function install(string $source, bool $enable = false, bool $replace = false): ExtensionManifest;
}
