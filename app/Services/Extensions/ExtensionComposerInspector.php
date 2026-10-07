<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Support\Facades\File;

/**
 * Reads the Composer files an extension package carries. The panel never runs Composer for
 * an extension, so these checks stand in for the ones Composer makes when it resolves a
 * single dependency graph.
 */
final class ExtensionComposerInspector
{
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
}
