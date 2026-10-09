<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Maintenance;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

#[Description('Clean orphaned .bak files created when modifying services.')]
#[Signature('p:maintenance:clean-service-backups')]
class CleanServiceBackupFilesCommand extends Command
{
    public const int BACKUP_THRESHOLD_MINUTES = 5;

    /**
     * Handle command execution.
     */
    public function handle(): void
    {
        $disk = Storage::disk();
        $files = $disk->files('services/.bak');

        collect($files)->each(function (string $file) use ($disk): void {
            $lastModified = Date::createFromTimestamp($disk->lastModified($file));
            if ($lastModified->diffInMinutes(now()) > self::BACKUP_THRESHOLD_MINUTES) {
                $disk->delete($file);
                $this->info(trans('command/messages.maintenance.deleting_service_backup', ['file' => basename($file)]));
            }
        });
    }
}
