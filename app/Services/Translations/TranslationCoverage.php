<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Translations;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Arr;
use Illuminate\Translation\Translator;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;

class TranslationCoverage
{
    public const string PLURAL_REGEX = '/^(.+)_(zero|one|two|few|many|other)$/D';

    public const string PLACEHOLDER_REGEX = '/(?<![\w:]):([A-Za-z_]\w*)/';

    public const string JSON_PLACEHOLDER_REGEX = '/\{\{\s*([\w.]+)\s*\}\}/';

    public function __construct(
        private readonly LanguageRegistry $languages,
        private readonly ExtensionRepository $extensions,
        private readonly Translator $translator,
    ) {}

    /**
     * @return array{locale: string, extension: string|null, total: int, translated: int, missing: list<string>, unchanged: list<string>, stale: list<string>, invalid: list<string>, unknown_placeholders: array<string, list<string>>, dropped_placeholders: array<string, list<string>>, conflicts: list<array{locale: string, key: string, providers: list<string>, winner: string}>}
     */
    public function report(string $locale, ?ExtensionManifest $pack = null): array
    {
        $target = $pack instanceof ExtensionManifest ? $this->languages->entries($pack, $locale) : $this->merged($locale);
        $namespaces = $pack instanceof ExtensionManifest ? $this->touchedNamespaces($target) : null;
        $base = $this->english($namespaces);

        $plurals = [];
        foreach (array_keys($base) as $key) {
            if (preg_match(self::PLURAL_REGEX, $key, $matches) === 1) {
                $plurals[$matches[1]] = true;
            }
        }

        $missing = [];
        $unchanged = [];
        $unknown = [];
        $dropped = [];
        foreach ($base as $key => $english) {
            $translationKey = preg_match(self::PLURAL_REGEX, $key, $matches) === 1 ? $matches[1].'_other' : $key;
            $value = $target[$key] ?? $target[$translationKey] ?? null;
            if (! is_string($value)) {
                $missing[] = $key;

                continue;
            }

            if ($value === $english) {
                $unchanged[] = $key;
            }

            $expected = $this->placeholders($key, $english);
            $found = $this->placeholders($key, $value);
            if (($extra = array_values(array_diff($found, $expected))) !== []) {
                $unknown[$key] = $extra;
            }

            if (($lost = array_values(array_diff($expected, $found))) !== []) {
                $dropped[$key] = $lost;
            }
        }

        $stale = [];
        $invalid = [];
        foreach ($target as $key => $value) {
            if (! is_string($value) && (! is_array($value) || $value !== [])) {
                $invalid[] = $key;
            }

            if (array_key_exists($key, $base)) {
                continue;
            }

            if (preg_match(self::PLURAL_REGEX, $key, $matches) === 1 && isset($plurals[$matches[1]])) {
                $extra = array_values(array_diff($this->placeholders($key, is_string($value) ? $value : ''), $this->placeholders($key, $base[$matches[1].'_other'] ?? '')));
                if ($extra !== []) {
                    $unknown[$key] = $extra;
                }

                continue;
            }

            $stale[] = $key;
        }

        $conflicts = $this->languages->conflicts($locale);
        if ($pack instanceof ExtensionManifest) {
            $conflicts = array_values(array_filter($conflicts, fn (array $conflict): bool => in_array($pack->id, $conflict['providers'], true)));
        }

        ksort($unknown);
        ksort($dropped);

        return [
            'locale' => $locale,
            'extension' => $pack?->id,
            'total' => count($base),
            'translated' => count($base) - count($missing) - count($unchanged),
            'missing' => $missing,
            'unchanged' => $unchanged,
            'stale' => $stale,
            'invalid' => $invalid,
            'unknown_placeholders' => $unknown,
            'dropped_placeholders' => $dropped,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * @param  list<string>|null  $namespaces
     * @return array<string, string>
     */
    private function english(?array $namespaces): array
    {
        $entries = [];
        if ($namespaces === null || in_array(LanguageRegistry::JSON_NAMESPACE, $namespaces, true)) {
            foreach ([lang_path(LanguageRegistry::FALLBACK.'.json'), ...array_map(fn (string $directory): string => $directory.DIRECTORY_SEPARATOR.LanguageRegistry::FALLBACK.'.json', $this->languages->jsonPaths())] as $file) {
                foreach (LanguageRegistry::decodeJson($file) as $key => $value) {
                    if (is_string($value)) {
                        $entries[LanguageRegistry::JSON_PREFIX.$key] = $value;
                    }
                }
            }
        }

        $core = $namespaces === null || in_array('*', $namespaces, true);
        foreach ($core ? LanguageRegistry::groupFiles(lang_path(LanguageRegistry::FALLBACK)) : [] as $group => $file) {
            foreach (LanguageRegistry::flatten($file) as $key => $value) {
                if (is_string($value)) {
                    $entries["{$group}.{$key}"] = $value;
                }
            }
        }

        foreach ($this->enabledNamespaces() as $namespace => $directory) {
            if ($namespaces !== null && ! in_array($namespace, $namespaces, true)) {
                continue;
            }

            foreach (LanguageRegistry::groupFiles($directory.DIRECTORY_SEPARATOR.LanguageRegistry::FALLBACK) as $group => $file) {
                foreach (LanguageRegistry::flatten($file) as $key => $value) {
                    if (is_string($value)) {
                        $entries["{$namespace}::{$group}.{$key}"] = $value;
                    }
                }
            }
        }

        return $entries;
    }

    /**
     * @return array<string, mixed>
     */
    private function merged(string $locale): array
    {
        $loader = $this->translator->getLoader();
        $keys = array_keys($this->english(null));
        foreach ($this->languages->packs() as $manifest) {
            if (in_array($locale, $manifest->languages, true)) {
                array_push($keys, ...array_keys($this->languages->entries($manifest, $locale)));
            }
        }

        $groups = [];
        foreach ($keys as $key) {
            if (str_starts_with((string) $key, LanguageRegistry::JSON_PREFIX)) {
                continue;
            }

            [$namespace, $item] = str_contains((string) $key, '::') ? explode('::', (string) $key, 2) : ['*', (string) $key];
            $group = explode('.', $item, 2)[0];
            $groups[$namespace.'|'.$group] = [$namespace, $group];
        }

        $entries = [];
        foreach ($loader->load($locale, '*', '*') as $key => $value) {
            $entries[LanguageRegistry::JSON_PREFIX.$key] = $value;
        }

        foreach ($groups as [$namespace, $group]) {
            $prefix = $namespace === '*' ? "{$group}." : "{$namespace}::{$group}.";
            foreach (Arr::dot($this->load($loader, $locale, $group, $namespace)) as $key => $value) {
                $entries[$prefix.$key] = $value;
            }
        }

        return $entries;
    }

    /**
     * @return array<mixed>
     */
    private function load(Loader $loader, string $locale, string $group, string $namespace): array
    {
        return $loader->load($locale, $group, $namespace);
    }

    /**
     * @param  array<string, mixed>  $entries
     * @return list<string>
     */
    private function touchedNamespaces(array $entries): array
    {
        $namespaces = [];
        foreach (array_keys($entries) as $key) {
            $namespace = match (true) {
                str_starts_with($key, LanguageRegistry::JSON_PREFIX) => LanguageRegistry::JSON_NAMESPACE,
                str_contains($key, '::') => explode('::', $key, 2)[0],
                default => '*',
            };
            $namespaces[$namespace] = true;
        }

        return array_keys($namespaces);
    }

    /**
     * @return array<string, string>
     */
    private function enabledNamespaces(): array
    {
        if (! config('extensions.enabled')) {
            return [];
        }

        $namespaces = [];
        foreach ($this->extensions->enabled() as $manifest) {
            $directory = $manifest->path('resources', 'lang');
            if (is_dir($directory)) {
                $namespaces['ext-'.$manifest->id] = $directory;
            }
        }

        return $namespaces;
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $key, string $value): array
    {
        preg_match_all(str_starts_with($key, LanguageRegistry::JSON_PREFIX) ? self::JSON_PLACEHOLDER_REGEX : self::PLACEHOLDER_REGEX, $value, $matches);

        $placeholders = array_values(array_unique(array_map(mb_strtolower(...), $matches[1])));
        sort($placeholders);

        return $placeholders;
    }
}
