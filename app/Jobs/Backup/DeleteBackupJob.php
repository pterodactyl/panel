<?php

namespace Pterodactyl\Jobs\Backup;

use Pterodactyl\Jobs\Job;
use Pterodactyl\Models\Backup;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Pterodactyl\Services\Backups\DeleteBackupService;

class DeleteBackupJob extends Job implements ShouldQueue
{
    use DispatchesJobs;
    use InteractsWithQueue;
    use SerializesModels;

    /**
     * @var \Pterodactyl\Models\Backup
     */
    public Backup $backup;

    /**
     * DeleteBackupJob constructor.
     */
    public function __construct(Backup $backup)
    {
        $this->queue = config('pterodactyl.queues.standard');
        $this->backup = $backup;
    }

    /**
     * Delete the backup.
     *
     * @throws \Throwable
     */
    public function handle(DeleteBackupService $deleteBackupService)
    {
        $deleteBackupService->handle($this->backup);
    }
}
