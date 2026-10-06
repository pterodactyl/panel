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
    public const int MAX_ARCHIVE_ENTRIES = 5000;

    public const int MAX_ARCHIVE_UNCOMPRESSED_BYTES = 200 * 1024 * 1024;

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

        try {
            $this->assertArchiveWithinLimits($zip, $archive);
        } catch (Throwable $throwable) {
            $zip->close();

            throw $throwable;
        }

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

    /**
     * @throws InvalidExtensionException
     */
    private function assertArchiveWithinLimits(ZipArchive $zip, string $archive): void
    {
        throw_if($zip->numFiles > self::MAX_ARCHIVE_ENTRIES, InvalidExtensionException::class, sprintf('Archive %s contains %d entries, more than the allowed %d.', $archive, $zip->numFiles, self::MAX_ARCHIVE_ENTRIES));

        $total = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            throw_if($stat === false, InvalidExtensionException::class, "Unable to read entry {$index} of archive {$archive}.");

            $total += $stat['size'];
            throw_if($total > self::MAX_ARCHIVE_UNCOMPRESSED_BYTES, InvalidExtensionException::class, sprintf('Archive %s expands to more than the allowed %d bytes.', $archive, self::MAX_ARCHIVE_UNCOMPRESSED_BYTES));
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
