<?php

namespace Pterodactyl\Services\WorldManager;

use Throwable;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Support\Arr;
use Pterodactyl\Models\InstallationLog;
use Pterodactyl\Models\InstalledWorld;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Jobs\WorldManager\InstallWorldFromCurseForgeJob;

class WorldInstallationService
{
    public function queueInstallation(Server $server, User $user, array $payload): InstallationLog
    {
        $log = InstallationLog::query()->create([
            'server_id' => $server->id,
            'requested_by' => $user->id,
            'status' => InstallationLog::STATUS_QUEUED,
            'progress_percent' => 0,
            'message' => 'Queued for installation.',
            'context' => $payload,
        ]);

        InstallWorldFromCurseForgeJob::dispatch($server->id, $user->id, $log->id, $payload);

        return $log;
    }

    public function completeInstallation(Server $server, User $user, InstallationLog $log, array $payload): InstalledWorld
    {
        return DB::transaction(function () use ($server, $user, $log, $payload) {
            $world = InstalledWorld::query()->create([
                'server_id' => $server->id,
                'installed_by' => $user->id,
                'name' => Arr::get($payload, 'name'),
                'directory' => Arr::get($payload, 'directory'),
                'curseforge_project_id' => Arr::get($payload, 'project_id'),
                'curseforge_file_id' => Arr::get($payload, 'file_id'),
                'minecraft_version' => Arr::get($payload, 'minecraft_version'),
                'is_active' => (bool) Arr::get($payload, 'activate_after_install', false),
                'metadata' => Arr::get($payload, 'metadata', []),
            ]);

            if ($world->is_active) {
                InstalledWorld::query()
                    ->where('server_id', $server->id)
                    ->where('id', '!=', $world->id)
                    ->update(['is_active' => false]);
            }

            $log->update([
                'installed_world_id' => $world->id,
                'status' => InstallationLog::STATUS_COMPLETED,
                'progress_percent' => 100,
                'message' => 'World installed successfully.',
            ]);

            return $world;
        });
    }

    public function failInstallation(InstallationLog $log, Throwable $exception): void
    {
        report($exception);

        $log->update([
            'status' => InstallationLog::STATUS_FAILED,
            'message' => $exception->getMessage(),
        ]);
    }
}
