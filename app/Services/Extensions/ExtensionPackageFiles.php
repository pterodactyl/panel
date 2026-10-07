<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\FilesystemChanges;
use Symfony\Component\Finder\Finder;

final readonly class ExtensionPackageFiles
{
    public function __construct(private ExtensionManifestValidator $validator, private FilesystemChanges $files) {}

    /** Whether the package is already the installed copy, so installing it replaces no files. */
    public function inPlace(ExtensionManifest $manifest, string $target): bool
    {
        return $manifest->directory === $target || realpath($manifest->directory) === realpath($target);
    }

    /** @param Closure(string): ExtensionManifest $callback */
    public function replace(ExtensionManifest $manifest, string $target, Closure $callback): ExtensionManifest
    {
        if ($this->inPlace($manifest, $target)) {
            return $callback($target);
        }

        File::ensureDirectoryExists(dirname($target));
        $staged = dirname($target).DIRECTORY_SEPARATOR.'.staging-'.Str::random(12);
        try {
            $this->copy($manifest->directory, $staged);
            $this->validator->fromDirectory($staged);

            return $this->files->run([$target => $staged], fn () => $callback($target));
        } finally {
            rescue(fn () => $this->files->delete($staged));
        }
    }

    /**
     * Copy a package file by file without following links: a link can point anywhere on this
     * machine, such as at the panel's .env, and following it would install that file (and
     * publish it, from dist). node_modules stays behind, since nothing loads it at runtime
     * and package managers fill it with links.
     */
    private function copy(string $from, string $to): void
    {
        File::ensureDirectoryExists($to);
        $finder = Finder::create()->in($from)->ignoreVCS(false)->ignoreDotFiles(false)->exclude('node_modules');

        foreach ($finder as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $problem = match (true) {
                $file->isLink() => 'a symbolic link',
                ! $file->isDir() && ! $file->isFile() => 'not a regular file',
                default => null,
            };
            throw_if($problem !== null, InvalidExtensionException::class, "Extension package file {$relative} is {$problem}; packages installed from a directory may only contain regular files.");

            $path = $to.DIRECTORY_SEPARATOR.$file->getRelativePathname();
            if ($file->isDir()) {
                File::ensureDirectoryExists($path);

                continue;
            }

            File::ensureDirectoryExists(dirname($path));
            throw_unless(File::copy($file->getPathname(), $path), InvalidExtensionException::class, 'Unable to stage extension package.');
        }
    }
}
