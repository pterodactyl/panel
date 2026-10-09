<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;

/**
 * Installing, enabling and removing extensions write to the extensions directory, the
 * published assets directory and a scratch directory in storage. Checking them before any
 * change starts reports a permission problem by the directory it affects, rather than as a
 * filesystem error partway through the change.
 */
final class ExtensionDirectories
{
    /**
     * @throws InvalidExtensionException
     */
    public function assertWritable(): void
    {
        $blocked = [];
        foreach ([
            config()->string('extensions.directory'),
            config()->string('extensions.assets_directory'),
            storage_path('app'.DIRECTORY_SEPARATOR.'extensions-tmp'),
        ] as $directory) {
            // A directory that does not exist yet is created inside the nearest one that does.
            $existing = $this->nearestExisting($directory);
            if (! is_dir($existing) || ! is_writable($existing)) {
                $blocked[$existing] = $existing;
            }
        }

        if ($blocked === []) {
            return;
        }

        throw new InvalidExtensionException(sprintf(
            'The panel cannot write to %s. Give %s write access to %s, then try again.',
            implode(', ', $blocked),
            $this->processUser(),
            count($blocked) === 1 ? 'it' : 'them',
        ));
    }

    private function nearestExisting(string $path): string
    {
        while (! file_exists($path) && dirname($path) !== $path) {
            $path = dirname($path);
        }

        return $path;
    }

    private function processUser(): string
    {
        $name = function_exists('posix_geteuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? null) : null;

        return is_string($name) ? "the {$name} user" : 'the user the panel runs as';
    }
}
