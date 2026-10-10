<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Pterodactyl\Events\Extensions\ExtensionLoadFailed;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Models\Extension;
use Pterodactyl\Support\JsonValueGuard;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class ExtensionRepository
{
    /** @var Collection<string, ExtensionManifest>|null */
    private ?Collection $discovered = null;

    /** @var Collection<string, ExtensionManifest>|null */
    private ?Collection $enabled = null;

    /** @var array<string, string> in-memory discovery errors keyed by directory name */
    private array $discoveryErrors = [];

    /** @var array<string, ExtensionSettings> */
    private array $settings = [];

    /** @var array<string, true> Provider failures in this application instance, retained across discovery refreshes. */
    private array $failedProviders = [];

    public function __construct(
        private readonly ExtensionManifestValidator $validator,
        private readonly ExtensionAssetPublisher $assets,
        private readonly Dispatcher $events,
        private readonly ExtensionSettingsRegistry $settingsRegistry,
        private readonly ExtensionCompatibility $compatibility,
    ) {}

    public function directory(): string
    {
        return mb_rtrim(JsonValueGuard::string(config('extensions.directory')), '/\\');
    }

    /**
     * @return Collection<string, ExtensionManifest>
     */
    public function discovered(): Collection
    {
        if ($this->discovered instanceof Collection) {
            return $this->discovered;
        }

        $manifests = [];
        foreach (glob($this->directory().'/*/'.ExtensionManifest::FILENAME) ?: [] as $file) {
            $directory = dirname($file);

            try {
                $manifest = $this->validator->fromDirectory($directory);
                if ($manifest->id !== basename($directory)) {
                    throw new InvalidExtensionException(sprintf('Extension directory "%s" must match its manifest id "%s".', basename($directory), $manifest->id));
                }

                $manifests[$manifest->id] = $manifest;
            } catch (InvalidExtensionException $exception) {
                $this->discoveryErrors[basename($directory)] = $exception->getMessage();
                Log::warning('[extensions] Skipping extension', ['directory' => $directory, 'exception' => $exception]);
            }
        }

        return $this->discovered = collect($manifests);
    }

    /**
     * @return array<string, string>
     */
    public function discoveryErrors(): array
    {
        return $this->discoveryErrors;
    }

    public function flushDiscovery(): void
    {
        $this->discovered = null;
        $this->enabled = null;
        $this->discoveryErrors = [];
    }

    /**
     * @return Collection<string, Extension>
     */
    public function records(): Collection
    {
        return rescue(function (): Collection {
            if (! Schema::hasTable('extensions')) {
                return $this->recordCollection([]);
            }

            $records = [];
            foreach (Extension::query()->get() as $extension) {
                $records[$extension->identifier] = $extension;
            }

            return $this->recordCollection($records);
        }, fn (): Collection => $this->recordCollection([]));
    }

    /**
     * @return Collection<string, ExtensionManifest>
     */
    public function enabled(): Collection
    {
        if ($this->enabled instanceof Collection) {
            return $this->enabled;
        }

        $configured = $this->configuredEnabled()->reject(fn (ExtensionManifest $manifest): bool => isset($this->failedProviders[$manifest->id]));
        $result = $this->compatibility->resolve($configured);
        foreach ($result['errors'] as $identifier => $reason) {
            $this->recordFailure($identifier, $reason, phase: 'compatibility');
        }

        return $this->enabled = $result['manifests'];
    }

    /** Whether an extension's runtime registrations may run right now. */
    public function isAvailable(string $identifier): bool
    {
        return config('extensions.enabled') && $this->enabled()->has($identifier);
    }

    /** @return Collection<string, ExtensionManifest> */
    public function configuredEnabled(): Collection
    {
        $discovered = $this->discovered();
        if ($discovered->isEmpty()) {
            return $discovered;
        }

        $records = $this->records();

        return $discovered->filter(function (ExtensionManifest $manifest) use ($records): bool {
            $record = $records->get($manifest->id);

            return $record !== null && $record->enabled;
        });
    }

    /**
     * The enabled frontend extensions for the SPA to load. Signed-in users receive every
     * extension with its frontend settings; everyone else only extensions that set
     * `ui.guest`, with the settings marked ->public().
     *
     * @return array<int, array{id: string, version: string, entry: string, prefix: string|null, translations: string|null, config: object, screens: list<ExtensionScreenDefinition>, components: list<string>, development: array{url: string, version: string}|null}>
     */
    public function frontendPayload(bool $authenticated): array
    {
        $enabled = $this->enabled()->filter(fn (ExtensionManifest $manifest): bool => $manifest->hasUi() && ($authenticated || $manifest->uiGuest));
        $configured = $authenticated ? $enabled : $enabled->filter(fn (ExtensionManifest $manifest): bool => ($this->settingsRegistry->get($manifest->id)?->publicConfigKeys() ?? []) !== []);
        if ($configured->isNotEmpty()) {
            rescue(fn () => ExtensionSettings::preload(array_values($configured->map(fn (ExtensionManifest $manifest): ExtensionSettings => $this->settings($manifest->id))->all())), report: false);
        }

        return $enabled->map(fn (ExtensionManifest $manifest): array => [
            'id' => $manifest->id,
            'version' => $manifest->version,
            'entry' => $this->assets->entryUrl($manifest),
            'prefix' => $manifest->uiPrefix,
            'translations' => $this->translationsRevision($manifest),
            'config' => (object) $this->frontendConfig($manifest->id, ! $authenticated),
            'screens' => $manifest->screens,
            'components' => $manifest->components,
            'development' => $this->assets->developmentPayload($manifest->id),
        ])
            ->values()
            ->all();
    }

    public function recordFailure(string $identifier, string $reason, ?Throwable $exception = null, string $phase = 'runtime'): void
    {
        if (in_array($phase, ['register', 'boot'], true)) {
            $this->failedProviders[$identifier] = true;
            $this->enabled = null;
        }

        // A failure that repeats on every request (reading field values, for one) is written once.
        rescue(fn (): int => Extension::query()
            ->where('identifier', $identifier)
            ->where(fn (Builder $query): Builder => $query->whereNull('error')->orWhere('error', '<>', $reason))
            ->update(['error' => $reason]), report: false);

        $context = array_filter(['exception' => $exception]);
        $exception instanceof Throwable
            ? Log::error("[extensions] Failed to load \"{$identifier}\": {$reason}", $context)
            : Log::warning("[extensions] Skipping \"{$identifier}\": {$reason}", $context);

        rescue(fn () => $this->events->dispatch(new ExtensionLoadFailed($identifier, $phase, $reason, $exception)));
    }

    /** @param list<string> $identifiers */
    public function clearErrors(array $identifiers): void
    {
        if ($identifiers === []) {
            return;
        }

        foreach ($identifiers as $identifier) {
            if (! isset($this->failedProviders[$identifier])) {
                continue;
            }

            unset($this->failedProviders[$identifier]);
            $this->enabled = null;
        }

        rescue(fn (): int => Extension::query()->whereIn('identifier', $identifiers)->whereNotNull('error')->update(['error' => null]), report: false);
    }

    public function settings(string $identifier): ExtensionSettings
    {
        // One instance per extension per request so the per-instance row cache
        // in ExtensionSettings actually coalesces reads.
        return $this->settings[$identifier] ??= new ExtensionSettings($identifier);
    }

    /**
     * Changes whenever the extension's translation files do. The frontend puts it in the
     * URL it loads those translations from, which lets the browser cache them for as long
     * as core translations and still pick up an installed or upgraded extension at once.
     */
    private function translationsRevision(ExtensionManifest $manifest): ?string
    {
        $directory = $manifest->path('resources', 'lang');
        if (! is_dir($directory)) {
            return null;
        }

        $files = array_map(
            fn (SplFileInfo $file): string => implode("\0", [$file->getRelativePathname(), $file->getSize(), $file->getMTime()]),
            File::allFiles($directory),
        );
        sort($files);

        return hash('xxh128', $manifest->version."\0".implode("\0", $files));
    }

    /**
     * @param  array<string, Extension>  $records
     * @return Collection<string, Extension>
     */
    private function recordCollection(array $records): Collection
    {
        return collect($records);
    }

    /**
     * The ->frontend()-marked settings for one extension, exposed to its bundle
     * as ctx.config; with $publicOnly just the ->public() ones, for guests.
     * Never allowed to break page rendering.
     *
     * @return ExtensionSettingValues
     */
    private function frontendConfig(string $identifier, bool $publicOnly): array
    {
        return rescue(fn (): array => $this->settingsRegistry->get($identifier)?->frontendConfig($publicOnly) ?? [], []);
    }
}
