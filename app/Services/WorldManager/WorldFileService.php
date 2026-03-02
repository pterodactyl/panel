<?php

namespace Pterodactyl\Services\WorldManager;

use Pterodactyl\Models\Server;
use Illuminate\Support\Str;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;

class WorldFileService
{
    public function __construct(private DaemonFileRepository $fileRepository)
    {
    }

    public function sanitizeDirectoryName(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->replaceMatches('/[^a-z0-9\-_]/', '-')
            ->trim('-')
            ->value();
    }

    public function assertWorldDirectoryAvailable(Server $server, string $directory): void
    {
        $contents = $this->fileRepository->setServer($server)->getDirectory('/');

        foreach ($contents as $file) {
            if (($file['name'] ?? null) === $directory) {
                throw new \RuntimeException('A world with this directory already exists.');
            }
        }
    }

    public function backupWorld(Server $server, string $directory): string
    {
        $target = sprintf('backups/%s-%s.zip', $directory, now()->format('YmdHis'));

        $this->fileRepository->setServer($server)->compressFiles('/', [$directory]);
        $this->fileRepository->setServer($server)->renameFiles('/', [[
            'from' => sprintf('%s.tar.gz', $directory),
            'to' => $target,
        ]]);

        return $target;
    }

    public function extractWorldArchive(Server $server, string $archiveName, string $targetDirectory): void
    {
        $this->fileRepository->setServer($server)->createDirectory($targetDirectory, '/');
        $this->fileRepository->setServer($server)->decompressFile('/', $archiveName);
    }

    public function deleteWorld(Server $server, string $directory): void
    {
        $this->fileRepository->setServer($server)->deleteFiles('/', [$directory]);
    }
}
