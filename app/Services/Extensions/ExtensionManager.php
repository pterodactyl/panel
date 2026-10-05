<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Support\Collection;
use Pterodactyl\Models\Extension;

/**
 * Public facade for extension discovery, state, and provider loading.
 *
 * The implementation is split into Laravel-bound services so install-time,
 * boot-time, and diagnostic paths can share the same behavior without coupling
 * everything to the boot manager.
 */
class ExtensionManager
{
    public function __construct(
        private readonly ExtensionRepository $extensions,
        private readonly ExtensionProviderLoader $providers,
    ) {}

    public function directory(): string
    {
        return $this->extensions->directory();
    }

    /**
     * @return Collection<string, ExtensionManifest>
     */
    public function discovered(): Collection
    {
        return $this->extensions->discovered();
    }

    /**
     * @return array<string, string>
     */
    public function discoveryErrors(): array
    {
        return $this->extensions->discoveryErrors();
    }

    public function flushDiscovery(): void
    {
        $this->extensions->flushDiscovery();
    }

    /**
     * @return Collection<string, Extension>
     */
    public function records(): Collection
    {
        return $this->extensions->records();
    }

    /**
     * @return Collection<string, ExtensionManifest>
     */
    public function enabled(): Collection
    {
        return $this->extensions->enabled();
    }

    /**
     * @return array<int, array{id: string, version: string, entry: string}>
     */
    public function frontendPayload(bool $authenticated): array
    {
        return $this->extensions->frontendPayload($authenticated);
    }

    public function registerProviders(): void
    {
        $this->providers->registerProviders($this->enabled());
    }

    public function bootProviders(): void
    {
        $this->providers->bootProviders();
    }

    public function settings(string $identifier): ExtensionSettings
    {
        return $this->extensions->settings($identifier);
    }
}
