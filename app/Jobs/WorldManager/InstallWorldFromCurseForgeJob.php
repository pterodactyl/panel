<?php

namespace Pterodactyl\Jobs\WorldManager;

use Throwable;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Bus\Queueable;
use Pterodactyl\Models\InstallationLog;
use Illuminate\Queue\SerializesModels;
use Pterodactyl\Services\WorldManager\CurseForgeService;
use Pterodactyl\Services\WorldManager\WorldFileService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Pterodactyl\Services\WorldManager\WorldInstallationService;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;

class InstallWorldFromCurseForgeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public function __construct(
        private int $serverId,
        private int $userId,
        private int $logId,
        private array $payload,
    ) {
    }

    public function handle(
        CurseForgeService $curseForgeService,
        WorldFileService $worldFileService,
        WorldInstallationService $installationService,
        DaemonFileRepository $fileRepository,
    ): void {
        $server = Server::query()->findOrFail($this->serverId);
        $user = User::query()->findOrFail($this->userId);
        $log = InstallationLog::query()->findOrFail($this->logId);

        try {
            $directory = $worldFileService->sanitizeDirectoryName($this->payload['name']);
            $worldFileService->assertWorldDirectoryAvailable($server, $directory);

            $log->update(['status' => InstallationLog::STATUS_DOWNLOADING, 'progress_percent' => 25]);

            $downloadUrl = $curseForgeService->getDownloadUrl(
                (int) $this->payload['project_id'],
                (int) $this->payload['file_id']
            );

            $archiveName = sprintf('tmp/world-%d-%d.zip', $this->payload['project_id'], $this->payload['file_id']);
            $fileRepository->setServer($server)->pull($downloadUrl, '/', [
                'filename' => basename($archiveName),
                'use_header' => true,
                'foreground' => true,
            ]);

            $log->update(['status' => InstallationLog::STATUS_EXTRACTING, 'progress_percent' => 70]);

            $worldFileService->extractWorldArchive($server, basename($archiveName), $directory);
            $fileRepository->setServer($server)->deleteFiles('/', [basename($archiveName)]);

            $installationService->completeInstallation($server, $user, $log, [
                ...$this->payload,
                'directory' => $directory,
            ]);
        } catch (Throwable $exception) {
            $installationService->failInstallation($log, $exception);
            throw $exception;
        }
    }
}
