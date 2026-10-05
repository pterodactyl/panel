<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Throwable;
use ZipArchive;

class ExtensionPackageLocator
{
    /**
     * @template T of object|ApiValue10
     *
     * @param  Closure(string): T  $callback
     * @return T
     */
    public function using(string $source, Closure $callback): mixed
    {
        $package = $this->locate($source);

        try {
            return $callback($package['directory']);
        } finally {
            if ($package['workdir'] !== null) {
                rescue(fn () => File::deleteDirectory($package['workdir']));
            }
        }
    }

    /** @return array{directory: string, workdir: string|null} */
    public function locate(string $source): array
    {
        if (is_dir($source)) {
            return ['directory' => $this->locatePackageRoot($source), 'workdir' => null];
        }

        throw_unless(is_file($source), InvalidExtensionException::class, "No extension package at {$source}.");
        $workdir = $this->extract($source);

        try {
            return ['directory' => $this->locatePackageRoot($workdir), 'workdir' => $workdir];
        } catch (Throwable $throwable) {
            rescue(fn () => File::deleteDirectory($workdir));

            throw $throwable;
        }
    }

    private function extract(string $archive): string
    {
        $zip = new ZipArchive;
        throw_if($zip->open($archive) !== true, InvalidExtensionException::class, "Unable to open archive {$archive}.");

        $workdir = storage_path('app'.DIRECTORY_SEPARATOR.'extensions-tmp'.DIRECTORY_SEPARATOR.Str::random(12));
        File::ensureDirectoryExists($workdir);

        try {
            throw_unless($zip->extractTo($workdir), InvalidExtensionException::class, "Unable to extract {$archive}.");

            return $workdir;
        } catch (Throwable $throwable) {
            rescue(fn () => File::deleteDirectory($workdir));

            throw $throwable;
        } finally {
            $zip->close();
        }
    }

    private function locatePackageRoot(string $directory): string
    {
        if (is_file($directory.DIRECTORY_SEPARATOR.ExtensionManifest::FILENAME)) {
            return $directory;
        }

        $candidates = glob($directory.'/*/'.ExtensionManifest::FILENAME) ?: [];
        if (count($candidates) === 1) {
            return dirname($candidates[0]);
        }

        throw new InvalidExtensionException('Could not locate a single '.ExtensionManifest::FILENAME." inside {$directory}.");
    }
}
