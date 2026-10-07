<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Illuminate\Support\Facades\File;

/**
 * Reads the Composer files an extension package carries. The panel never runs Composer for
 * an extension, so these checks stand in for the ones Composer makes when it resolves a
 * single dependency graph.
 */
final class ExtensionComposerInspector
{
    /** @var array<string, string>|null version of each package the panel loads, by name */
    private ?array $panelPackages = null;

    /**
     * The suffix of the autoloader class a package's vendor/autoload.php declares
     * (`ComposerAutoloaderInit<suffix>`), or null when it has none. Composer derives the
     * suffix from the lock file unless `config.autoloader-suffix` sets it, so two packages
     * installed from the same requirements share it, and PHP cannot declare the second.
     */
    public static function autoloaderSuffix(string $directory): ?string
    {
        $path = mb_rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
        if (! is_file($path)) {
            return null;
        }

        return preg_match('/\bComposerAutoloaderInit([A-Za-z0-9_]+)::getLoader\(\)/', File::get($path), $match) === 1 ? $match[1] : null;
    }

    /** Whether a Composer autoloader with this suffix has already been declared in this process. */
    public static function autoloaderLoaded(string $suffix): bool
    {
        return class_exists('ComposerAutoloaderInit'.$suffix, false);
    }

    /**
     * What Composer would object to in the dependencies a package bundles, none of which
     * stops it from loading: development packages an install without --no-dev left in
     * vendor/, composer.json requirements the panel's copy of a package does not satisfy,
     * and bundled packages the panel loads at another version. A class the panel can load
     * always comes from the panel, so the extension runs with the panel's copy of those.
     *
     * @return list<string>
     */
    public function warnings(ExtensionManifest $manifest): array
    {
        $warnings = [];
        $installed = $this->installed($manifest->path('vendor', 'composer', 'installed.json'));
        if ($installed['dev'] !== []) {
            $warnings[] = sprintf('vendor/ includes development packages (%s). Run composer install --no-dev --optimize-autoloader before packing.', $this->summarize($installed['dev']));
        }

        $panel = $this->panelPackages();
        $unmet = [];
        foreach ($this->requirements($manifest->path('composer.json')) as $package => $constraint) {
            $version = $panel[$package] ?? null;
            if ($version !== null && ! rescue(fn (): bool => Semver::satisfies($version, $constraint), false, false)) {
                $unmet[$package] = sprintf('%s %s (the panel loads %s)', $package, $constraint, $version);
            }
        }

        if ($unmet !== []) {
            $warnings[] = sprintf("composer.json requires packages the panel already loads at versions it does not have, and the panel's copies are used: %s.", implode(', ', $unmet));
        }

        $shadowed = [];
        foreach ($installed['packages'] as $package => $version) {
            if (isset($panel[$package]) && ! isset($unmet[$package]) && $this->normalize($panel[$package]) !== $this->normalize($version)) {
                $shadowed[] = sprintf('%s (bundled %s, panel %s)', $package, $version, $panel[$package]);
            }
        }

        if ($shadowed !== []) {
            $warnings[] = sprintf("vendor/ bundles other versions of packages the panel already loads, and the panel's copies are used: %s.", implode(', ', $shadowed));
        }

        return $warnings;
    }

    /**
     * The packages Composer installed into a vendor/ directory, by name, and the
     * development packages among them when it installed those too.
     *
     * @return array{packages: array<string, string>, dev: list<string>}
     */
    private function installed(string $path): array
    {
        $installed = is_file($path) ? json_decode(File::get($path), true) : null;
        if (! is_array($installed)) {
            return ['packages' => [], 'dev' => []];
        }

        // Composer 1 wrote the package list itself; Composer 2 nests it under "packages".
        $list = array_is_list($installed) ? $installed : $installed['packages'] ?? [];
        $packages = [];
        foreach (is_array($list) ? $list : [] as $package) {
            if (is_array($package) && is_string($package['name'] ?? null) && is_string($package['version'] ?? null)) {
                $packages[$package['name']] = $package['version'];
            }
        }

        $dev = ($installed['dev'] ?? false) === true && is_array($installed['dev-package-names'] ?? null) ? $installed['dev-package-names'] : [];

        return ['packages' => $packages, 'dev' => array_values(array_filter($dev, is_string(...)))];
    }

    /**
     * The package requirements of a composer.json, leaving out PHP, its extensions and
     * Composer itself.
     *
     * @return array<string, string>
     */
    private function requirements(string $path): array
    {
        $composer = is_file($path) ? json_decode(File::get($path), true) : null;
        $require = is_array($composer) ? $composer['require'] ?? [] : [];

        $requirements = [];
        foreach (is_array($require) ? $require : [] as $package => $constraint) {
            if (is_string($package) && is_string($constraint) && str_contains($package, '/')) {
                $requirements[$package] = $constraint;
            }
        }

        return $requirements;
    }

    /**
     * The version of every package the panel loads in production: the ones it installed and
     * the ones those replace, such as the illuminate/* components of laravel/framework.
     *
     * @return array<string, string>
     */
    private function panelPackages(): array
    {
        if ($this->panelPackages !== null) {
            return $this->panelPackages;
        }

        $path = base_path('vendor/composer/installed.php');
        $installed = is_file($path) ? File::getRequire($path) : null;
        $versions = is_array($installed) ? $installed['versions'] ?? [] : [];

        $packages = [];
        foreach (is_array($versions) ? $versions : [] as $name => $package) {
            if (! is_string($name) || ! is_array($package) || ($package['dev_requirement'] ?? false) === true) {
                continue;
            }

            $replaced = is_array($package['replaced'] ?? null) ? reset($package['replaced']) : null;
            $version = $package['pretty_version'] ?? $replaced;
            if (is_string($version)) {
                $packages[$name] = $version;
            }
        }

        return $this->panelPackages = $packages;
    }

    private function normalize(string $version): string
    {
        return rescue(fn (): string => (new VersionParser)->normalize($version), $version, false);
    }

    /** @param list<string> $names */
    private function summarize(array $names): string
    {
        $shown = implode(', ', array_slice($names, 0, 3));

        return count($names) > 3 ? sprintf('%s and %d more', $shown, count($names) - 3) : $shown;
    }
}
