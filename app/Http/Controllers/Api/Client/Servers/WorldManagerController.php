<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Server;
use Illuminate\Http\Response;
use Pterodactyl\Models\InstalledWorld;
use Pterodactyl\Models\InstallationLog;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Worlds\InstallWorldRequest;
use Pterodactyl\Services\WorldManager\CurseForgeService;
use Pterodactyl\Services\WorldManager\WorldFileService;
use Pterodactyl\Services\WorldManager\WorldInstallationService;
use Pterodactyl\Http\Requests\Api\Client\Servers\Worlds\SearchWorldsRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Worlds\ManageInstalledWorldRequest;

class WorldManagerController extends ClientApiController
{
    public function __construct(
        private CurseForgeService $curseForgeService,
        private WorldInstallationService $installationService,
        private WorldFileService $worldFileService,
    ) {
        parent::__construct();
    }

    public function search(SearchWorldsRequest $request, Server $server): array
    {
        return $this->curseForgeService->searchWorlds(
            $request->string('q')->toString(),
            $request->input('minecraft_version'),
            $request->integer('page', 1),
            $request->integer('page_size', 12),
            $request->input('sort', 'featured')
        );
    }

    public function versions(SearchWorldsRequest $request, Server $server, int $projectId): array
    {
        return [
            'data' => $this->curseForgeService->getFiles($projectId, $request->input('minecraft_version')),
        ];
    }

    public function install(InstallWorldRequest $request, Server $server): array
    {
        $log = $this->installationService->queueInstallation($server, $request->user(), $request->validated());

        return [
            'object' => 'installation_log',
            'attributes' => [
                'id' => $log->id,
                'status' => $log->status,
                'progress_percent' => $log->progress_percent,
                'message' => $log->message,
            ],
        ];
    }

    public function installed(SearchWorldsRequest $request, Server $server): array
    {
        return [
            'data' => InstalledWorld::query()->where('server_id', $server->id)->orderByDesc('id')->get(),
        ];
    }

    public function logs(SearchWorldsRequest $request, Server $server): array
    {
        return [
            'data' => InstallationLog::query()->where('server_id', $server->id)->latest()->limit(25)->get(),
        ];
    }

    public function rename(ManageInstalledWorldRequest $request, Server $server, InstalledWorld $world): JsonResponse
    {
        abort_if($world->server_id !== $server->id, Response::HTTP_NOT_FOUND);

        $world->update(['name' => $request->input('name')]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function delete(ManageInstalledWorldRequest $request, Server $server, InstalledWorld $world): JsonResponse
    {
        abort_if($world->server_id !== $server->id, Response::HTTP_NOT_FOUND);

        $this->worldFileService->deleteWorld($server, $world->directory);
        $world->delete();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function activate(ManageInstalledWorldRequest $request, Server $server, InstalledWorld $world): JsonResponse
    {
        abort_if($world->server_id !== $server->id, Response::HTTP_NOT_FOUND);

        InstalledWorld::query()->where('server_id', $server->id)->update(['is_active' => false]);
        $world->update(['is_active' => true]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function backup(ManageInstalledWorldRequest $request, Server $server, InstalledWorld $world): array
    {
        abort_if($world->server_id !== $server->id, Response::HTTP_NOT_FOUND);

        return [
            'backup_path' => $this->worldFileService->backupWorld($server, $world->directory),
        ];
    }
}
