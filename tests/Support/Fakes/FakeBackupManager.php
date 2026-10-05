<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Support\Fakes;

use League\Flysystem\FilesystemAdapter;
use Pterodactyl\Extensions\Backups\BackupManager;

class FakeBackupManager extends BackupManager
{
    /** @var list<string|null> */
    public array $adapterCalls = [];

    public function __construct(private FakeS3Filesystem $filesystem) {}

    public function adapter(?string $name = null): FilesystemAdapter
    {
        $this->adapterCalls[] = $name;

        return $this->filesystem;
    }
}
