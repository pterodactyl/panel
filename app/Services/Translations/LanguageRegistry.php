<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Translations;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Locale;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Symfony\Component\Finder\SplFileInfo;

class LanguageRegistry
{
    public const string FALLBACK = 'en';

    public const string CORE = 'core';

    public const string JSON_NAMESPACE = 'translation';

    public const string JSON_PREFIX = 'json:';

    /** @var list<string>|null */
    private ?array $coreLocales = null;

    public function __construct(private readonly ExtensionRepository $extensions) {}

    /**
     * @return list<string>
     */
    public static function fingerprint(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = array_map(
            fn (SplFileInfo $file): string => implode("\0", [$file->getRelativePathname(), $file->getSize(), $file->getMTime()]),
            File::allFiles($directory),
        );
        sort($files);

        return $files;
    }

    /**
     * @return array<string, string>
     */
    public static function groupFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $groups = [];
        foreach (File::allFiles($directory) as $file) {
            if ($file->getExtension() === 'php') {
                $groups[str_replace(DIRECTORY_SEPARATOR, '/', mb_substr($file->getRelativePathname(), 0, -4))] = $file->getPathname();
            }
        }

        ksort($groups);

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeJson(string $file): array
    {
        if (! is_file($file)) {
            return [];
        }

        $lines = json_decode(File::get($file), true);
        $entries = [];
        foreach (is_array($lines) ? $lines : [] as $key => $value) {
            $entries[(string) $key] = $value;
        }

        return $entries;
    }

    /**
     * @return array<string, mixed>
     */
    public static function flatten(string $file): array
    {
        $lines = File::getRequire($file);
        $entries = [];
        foreach (is_array($lines) ? Arr::dot($lines) : [] as $key => $value) {
            $entries[(string) $key] = $value;
        }

        return $entries;
    }

    /**
     * @return Collection<string, ExtensionManifest>
     */
    public function packs(): Collection
    {
        if (! config('extensions.enabled')) {
            return $this->manifests([]);
        }

        return $this->extensions->enabled()->filter(fn (ExtensionManifest $manifest): bool => $manifest->hasLanguages());
    }

    /**
     * @return list<string>
     */
    public function coreLocales(): array
    {
        return $this->coreLocales ??= array_values(array_unique(array_filter(
            [...array_map(basename(...), glob(lang_path('*'), GLOB_ONLYDIR) ?: []), ...array_map(fn (string $file): string => basename($file, '.json'), glob(lang_path('*.json')) ?: [])],
            fn (string $locale): bool => preg_match(ExtensionManifest::LOCALE_REGEX, $locale) === 1,
        )));
    }

    /**
     * @return array<string, list<string>>
     */
    public function sources(): array
    {
        $sources = array_fill_keys($this->coreLocales(), [self::CORE]);
        foreach ($this->packs() as $manifest) {
            foreach ($manifest->languages as $locale) {
                $sources[$locale][] = $manifest->id;
            }
        }

        ksort($sources);

        return $sources;
    }

    /**
     * @return array<string, string>
     */
    public function available(bool $localize = false): array
    {
        $languages = [];
        foreach (array_keys($this->sources()) as $locale) {
            $name = Locale::getDisplayName($locale, $localize ? $locale : self::FALLBACK);
            $languages[$locale] = Str::title($name === false ? $locale : $name);
        }

        return $languages;
    }

    public function isAvailable(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, $this->sources());
    }

    public function panelDefault(): string
    {
        $locale = config()->string('app.locale', self::FALLBACK);

        return $this->isAvailable($locale) ? $locale : self::FALLBACK;
    }

    public function resolveFor(?User $user): string
    {
        $language = $user?->language;

        return $this->isAvailable($language) ? (string) $language : $this->panelDefault();
    }

    public function revision(): ?string
    {
        $parts = [];
        foreach ($this->packs() as $manifest) {
            $parts[] = implode("\0", [$manifest->id, $manifest->version, ...self::fingerprint($manifest->languagesPath())]);
        }

        foreach ($this->jsonPaths() as $id => $directory) {
            $files = array_map(fn (string $file): string => implode("\0", [basename($file), filesize($file), filemtime($file)]), glob($directory.DIRECTORY_SEPARATOR.'*.json') ?: []);
            $parts[] = implode("\0", [$id, ...$files]);
        }

        return $parts === [] ? null : hash('xxh128', implode("\0\0", $parts));
    }

    /**
     * @return array<string, string>
     */
    public function jsonPaths(): array
    {
        if (! config('extensions.enabled')) {
            return [];
        }

        $paths = [];
        foreach ($this->extensions->enabled() as $manifest) {
            $directory = $manifest->path('resources', 'lang');
            if ((glob($directory.DIRECTORY_SEPARATOR.'*.json') ?: []) !== []) {
                $paths[$manifest->id] = $directory;
            }
        }

        return $paths;
    }

    /**
     * @return array<string, mixed>
     */
    public function entries(ExtensionManifest $manifest, string $locale): array
    {
        $entries = [];
        foreach (self::decodeJson($manifest->languagesPath("{$locale}.json")) as $key => $value) {
            $entries[self::JSON_PREFIX.$key] = $value;
        }

        foreach (self::groupFiles($manifest->languagesPath($locale)) as $group => $file) {
            foreach (self::flatten($file) as $key => $value) {
                $entries["{$group}.{$key}"] = $value;
            }
        }

        $vendor = $manifest->languagesPath('vendor');
        foreach (glob($vendor.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [] as $namespace) {
            foreach (self::groupFiles($namespace.DIRECTORY_SEPARATOR.$locale) as $group => $file) {
                foreach (self::flatten($file) as $key => $value) {
                    $entries[basename($namespace)."::{$group}.{$key}"] = $value;
                }
            }
        }

        return $entries;
    }

    /**
     * @return list<array{locale: string, key: string, providers: list<string>, winner: string}>
     */
    public function conflicts(?string $locale = null): array
    {
        $packs = $this->packs();
        $dependencies = $this->dependencyClosure($packs);
        $conflicts = [];
        foreach ($locale === null ? array_keys($this->sources()) : [$locale] as $code) {
            $providers = [];
            foreach ($packs as $manifest) {
                if (in_array($code, $manifest->languages, true)) {
                    foreach (array_keys($this->entries($manifest, $code)) as $key) {
                        $providers[(string) $key][] = $manifest->id;
                    }
                }
            }

            foreach ($providers as $key => $ids) {
                $winner = $ids[array_key_last($ids)];
                $unrelated = array_filter(array_slice($ids, 0, -1), fn (string $id): bool => ! in_array($id, $dependencies[$winner] ?? [], true));
                if ($unrelated !== []) {
                    $conflicts[] = ['locale' => $code, 'key' => $key, 'providers' => $ids, 'winner' => $winner];
                }
            }
        }

        return $conflicts;
    }

    /**
     * @param  list<array{locale: string, key: string, providers: list<string>, winner: string}>  $conflicts
     * @return list<array{locale: string, extension: string, winner: string, keys: int}>
     */
    public function conflictSummary(string $identifier, array $conflicts): array
    {
        $summary = [];
        foreach ($conflicts as $conflict) {
            if (! in_array($identifier, $conflict['providers'], true)) {
                continue;
            }

            foreach ($conflict['providers'] as $other) {
                if ($other === $identifier) {
                    continue;
                }

                $index = "{$conflict['locale']}|{$other}";
                $summary[$index] ??= ['locale' => $conflict['locale'], 'extension' => $other, 'winner' => $conflict['winner'], 'keys' => 0];
                $summary[$index]['keys']++;
            }
        }

        ksort($summary);

        return array_values($summary);
    }

    /**
     * @param  array<string, ExtensionManifest>  $manifests
     * @return Collection<string, ExtensionManifest>
     */
    private function manifests(array $manifests): Collection
    {
        return collect($manifests);
    }

    /**
     * @param  Collection<string, ExtensionManifest>  $packs
     * @return array<string, list<string>>
     */
    private function dependencyClosure(Collection $packs): array
    {
        $direct = $this->extensions->enabled()->map(fn (ExtensionManifest $manifest): array => array_keys($manifest->dependencies()))->all();
        $closure = [];
        foreach ($packs->keys() as $id) {
            $seen = [];
            $queue = $direct[$id] ?? [];
            while ($queue !== []) {
                $next = array_shift($queue);
                if (! isset($seen[$next])) {
                    $seen[$next] = true;
                    array_push($queue, ...($direct[$next] ?? []));
                }
            }

            $closure[$id] = array_keys($seen);
        }

        return $closure;
    }
}
