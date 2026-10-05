<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Maintenance;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Date;

#[Description('Clean orphaned .bak files created when modifying services.')]
#[Signature('p:maintenance:clean-service-backups')]
class CleanServiceBackupFilesCommand extends Command
{
    public const int BACKUP_THRESHOLD_MINUTES = 5;

    protected Filesystem $disk;

    /**
     * CleanServiceBackupFilesCommand constructor.
     */
    public function __construct(FilesystemFactory $filesystem)
    {
        parent::__construct();

        $this->disk = $filesystem->disk();
    }

    /**
     * Handle command execution.
     */
    public function handle(): void
    {
        $files = $this->disk->files('services/.bak');

        collect($files)->each(function (string $file): void {
            $lastModified = Date::createFromTimestamp($this->disk->lastModified($file));
            if ($lastModified->diffInMinutes(now()) > self::BACKUP_THRESHOLD_MINUTES) {
                $this->disk->delete($file);
                $this->info(trans('command/messages.maintenance.deleting_service_backup', ['file' => basename($file)]));
            }
        });
    }
}
