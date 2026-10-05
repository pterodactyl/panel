<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

/**
 * The settings definitions extensions have registered this boot, keyed by
 * extension id. Populated by ExtensionProvider::registerSettings() while
 * providers boot; consumed by the admin extensions API (auto-rendered settings
 * forms) and the frontend payload (ctx.config).
 */
class ExtensionSettingsRegistry
{
    /** @var array<string, ExtensionSettingsDefinition> */
    private array $definitions = [];

    public function register(string $identifier, ExtensionSettingsDefinition $definition): void
    {
        $this->definitions[$identifier] = $definition;
    }

    public function get(string $identifier): ?ExtensionSettingsDefinition
    {
        return $this->definitions[$identifier] ?? null;
    }

    public function unregister(string $identifier): void
    {
        unset($this->definitions[$identifier]);
    }

    public function has(string $identifier): bool
    {
        return isset($this->definitions[$identifier]);
    }
}
