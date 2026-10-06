<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Pterodactyl\Data\Extensions\ExtensionJobSnapshot;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;

/** Shared snapshots survive reconnects and worker boundaries for the configured retention period. */
final class ExtensionJobProgress
{
    public function begin(string $extension, User $user, ?Server $server = null, ?string $permission = null): ExtensionJobSnapshot
    {
        throw_unless(preg_match(ExtensionManifest::ID_REGEX, $extension), InvalidArgumentException::class, 'Invalid extension identifier.');
        throw_if($permission !== null && (! $server instanceof Server || ! preg_match('/'.ExtensionPermissionRegistry::PERMISSION_PATTERN.'/D', $permission) || ! str_starts_with($permission, 'ext.'.$extension.'.')), InvalidArgumentException::class, 'Progress permissions must belong to the extension and require a server.');
        $snapshot = new ExtensionJobSnapshot(Str::uuid()->toString(), $extension, $user->uuid, $server?->uuid, $permission, 'running', 0, '', 1, now()->toIso8601String());
        $this->store($snapshot);

        return $snapshot;
    }

    /** @param 'running'|'completed'|'failed' $status */
    public function update(string $extension, string $id, int $percent, string $message = '', string $status = 'running'): ExtensionJobSnapshot
    {
        throw_if($percent < 0 || $percent > 100 || mb_strlen($message) > 1000, InvalidArgumentException::class, 'Invalid progress update.');

        $lock = Cache::lock($this->key($extension, $id).':lock', 10);
        $lock->block(5);
        try {
            $previous = $this->find($extension, $id);
            throw_if(! $previous instanceof ExtensionJobSnapshot, InvalidArgumentException::class, 'Progress has expired or does not exist.');
            throw_if($previous->status !== 'running', InvalidArgumentException::class, 'Completed progress cannot be updated.');
            throw_if($percent < $previous->percent, InvalidArgumentException::class, 'Progress cannot move backwards.');
            $snapshot = new ExtensionJobSnapshot($id, $extension, $previous->userUuid, $previous->serverUuid, $previous->permission, $status, $status === 'completed' ? 100 : $percent, $message, $previous->sequence + 1, now()->toIso8601String());
            $this->store($snapshot);

            return $snapshot;
        } finally {
            $lock->release();
        }
    }

    public function find(string $extension, string $id): ?ExtensionJobSnapshot
    {
        $snapshot = Cache::get($this->key($extension, $id));

        ExtensionJobSnapshot::assertCacheValue($snapshot);

        return $snapshot;
    }

    public function visible(string $extension, string $id, User $user, ?Server $server = null): ExtensionJobSnapshot
    {
        $snapshot = $this->find($extension, $id);
        abort_if(! $snapshot instanceof ExtensionJobSnapshot || $snapshot->serverUuid !== $server?->uuid, 404);
        if (! $server instanceof Server || $snapshot->permission === null) {
            abort_unless($snapshot->userUuid === $user->uuid || $user->root_admin, 404);
        } else {
            abort_unless($user->can($snapshot->permission, $server), 404);
        }

        return $snapshot;
    }

    private function store(ExtensionJobSnapshot $snapshot): void
    {
        Cache::put($this->key($snapshot->extension, $snapshot->id), $snapshot, max(60, JsonValueGuard::integer(config('extensions.progress_retention_seconds'))));
    }

    private function key(string $extension, string $id): string
    {
        return 'extensions:progress:'.hash('sha256', $extension.'\0'.$id);
    }
}
