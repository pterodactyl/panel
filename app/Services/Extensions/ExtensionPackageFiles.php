<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\FilesystemChanges;

final readonly class ExtensionPackageFiles
{
    public function __construct(private ExtensionManifestValidator $validator, private FilesystemChanges $files) {}

    /** @param Closure(string): ExtensionManifest $callback */
    public function replace(ExtensionManifest $manifest, string $target, Closure $callback): ExtensionManifest
    {
        if ($manifest->directory === $target || realpath($manifest->directory) === realpath($target)) {
            return $callback($target);
        }

        File::ensureDirectoryExists(dirname($target));
        $staged = dirname($target).DIRECTORY_SEPARATOR.'.staging-'.Str::random(12);
        try {
            throw_unless(File::copyDirectory($manifest->directory, $staged), InvalidExtensionException::class, 'Unable to stage extension package.');
            $this->validator->fromDirectory($staged);

            return $this->files->run([$target => $staged], fn () => $callback($target));
        } finally {
            rescue(fn () => $this->files->delete($staged));
        }
    }
}
