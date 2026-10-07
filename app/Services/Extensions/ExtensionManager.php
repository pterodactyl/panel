<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

/**
 * Kept for extension code written against earlier scaffolds, which read settings through
 * `app(ExtensionManager::class)->settings('<id>')`.
 *
 * @deprecated use the Pterodactyl\Facades\Extensions facade: `Extensions::settings('<id>')`
 */
class ExtensionManager
{
    public function __construct(private readonly ExtensionRepository $extensions) {}

    /** @deprecated use `Extensions::settings('<id>')` */
    public function settings(string $identifier): ExtensionSettings
    {
        return $this->extensions->settings($identifier);
    }
}
