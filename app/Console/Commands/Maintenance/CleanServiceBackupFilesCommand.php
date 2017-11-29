<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Maintenance;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class CleanServiceBackupFilesCommand extends Command
{
    const BACKUP_THRESHOLD_MINUTES = 5;

    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var string
     */
    protected $description = 'Clean orphaned .bak files created when modifying services.';

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $disk;

    /**
     * @var string
     */
    protected $signature = 'p:maintenance:clean-service-backups';

    /**
     * CleanServiceBackupFilesCommand constructor.
     *
     * @param \Carbon\Carbon                           $carbon
     * @param \Illuminate\Contracts\Filesystem\Factory $filesystem
     */
    public function __construct(Carbon $carbon, FilesystemFactory $filesystem)
    {
        parent::__construct();

        $this->carbon = $carbon;
        $this->disk = $filesystem->disk();
    }

    /**
     * Handle command execution.
     */
    public function handle()
    {
        $files = $this->disk->files('services/.bak');

        collect($files)->each(function ($file) {
            $lastModified = $this->carbon->timestamp($this->disk->lastModified($file));
            if ($lastModified->diffInMinutes($this->carbon->now()) > self::BACKUP_THRESHOLD_MINUTES) {
                $this->disk->delete($file);
                $this->info(trans('command/messages.maintenance.deleting_service_backup', ['file' => $file]));
            }
        });
    }
}
