<?php

declare(strict_types=1);

namespace Pterodactyl\Services;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class FilesystemChanges
{
    /**
     * Replace or remove paths, retaining originals until the operation succeeds.
     *
     * @template T of object|ApiValue10|void
     *
     * @param  array<string, string|null>  $replacements  Target paths mapped to staged paths, or null for removal.
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(array $replacements, Closure $callback): mixed
    {
        $backups = [];

        try {
            foreach ($replacements as $target => $staged) {
                $backup = File::exists($target) ? dirname($target).'/.previous-'.Str::random(12) : null;
                if ($backup !== null) {
                    $this->move($target, $backup);
                }

                $backups[$target] = $backup;
                if ($staged !== null) {
                    File::ensureDirectoryExists(dirname($target));
                    $this->move($staged, $target);
                }
            }

            $result = $callback();
        } catch (Throwable $throwable) {
            foreach (array_reverse($backups, true) as $target => $backup) {
                rescue(fn () => $this->restore($target, $backup));
            }

            throw $throwable;
        }

        foreach ($backups as $backup) {
            if ($backup !== null) {
                rescue(fn () => $this->delete($backup));
            }
        }

        return $result;
    }

    public function delete(string $path): void
    {
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        } else {
            File::delete($path);
        }

        clearstatcache(true, $path);
        throw_if(File::exists($path), RuntimeException::class, "Unable to remove files at {$path}.");
    }

    private function restore(string $target, ?string $backup): void
    {
        $this->delete($target);
        if ($backup !== null) {
            $this->move($backup, $target);
        }
    }

    private function move(string $from, string $to): void
    {
        throw_unless(File::move($from, $to), RuntimeException::class, "Unable to move {$from} to {$to}; the original remains at {$from}.");
    }
}
