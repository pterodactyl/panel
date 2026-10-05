<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Support\Facades\File;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Support\JsonValueGuard;

final class ExtensionLock
{
    /** @var array<string, resource> */
    private array $locks = [];

    private int $depth = 0;

    /**
     * @template T of object|ApiValue10|void
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(string $identifier, Closure $callback): mixed
    {
        $this->acquire($identifier);

        try {
            return $callback();
        } finally {
            $this->release();
        }
    }

    public function acquire(string $identifier): void
    {
        if (isset($this->locks['packages'])) {
            $this->depth++;

            return;
        }

        $directory = mb_rtrim(JsonValueGuard::string(config('extensions.directory')), '/\\').DIRECTORY_SEPARATOR.'.locks';
        File::ensureDirectoryExists($directory);
        $lock = fopen($directory.DIRECTORY_SEPARATOR.'packages', 'c');
        throw_if($lock === false, InvalidExtensionException::class, 'Unable to lock extension package.');
        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            throw new InvalidExtensionException("Another operation is updating extensions; cannot update \"{$identifier}\" yet.");
        }

        $this->locks['packages'] = $lock;
        $this->depth = 1;
    }

    public function release(): void
    {
        if (--$this->depth > 0) {
            return;
        }

        $lock = $this->locks['packages'];
        unset($this->locks['packages']);
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
