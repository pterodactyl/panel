<?php

namespace Pterodactyl\Jobs\Backups;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pterodactyl\Exceptions\Service\Backup\BackupLockedException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Backups\DeleteBackupService;

class DeleteAll implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * The server instance to delete all backups for.
     *
     * @var Server $server
     */
    private Server $server;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Server $server)
    {
        $this->server = $server;

        // Load the backup relation.
        $this->load('backups');

        // Run this job on the low priority queue.
        $this->onQueue(env('QUEUE_LOW', 'low'));
    }

    /**
     * Get the unique ID for the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return $this->server->uuid;
    }

    /**
     * Execute the job to delete all backups for a server.
     *
     * @param DeleteBackupService $deleteBackupService
     *
     * @return void
     */
    public function handle(DeleteBackupService $deleteBackupService): void
    {
        $this->server->backups->each(function ($backup) use ($deleteBackupService) {
            try {
                $deleteBackupService->handle($backup);

                Activity::event('server:backup.delete')
                    ->subject($backup)
                    ->property(['name' => $backup->name, 'failed' => !$backup->is_successful])
                    ->log();
            } catch (BackupLockedException|\Throwable $e) {
                // Do nothing.
            }
        });
    }
}
