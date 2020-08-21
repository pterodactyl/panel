<?php

namespace Pterodactyl\Console\Commands\Maintenance;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Illuminate\Console\Command;
use Pterodactyl\Repositories\Eloquent\BackupRepository;

class PruneOrphanedBackupsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'p:maintenance:prune-backups {--since-minutes=30}';

    /**
     * @var string
     */
    protected $description = 'Removes all backups that have existed for more than "n" minutes which are not marked as completed.';

    /**
     * @param \Pterodactyl\Repositories\Eloquent\BackupRepository $repository
     */
    public function handle(BackupRepository $repository)
    {
        $since = $this->option('since-minutes');
        if (!is_digit($since)) {
            throw new InvalidArgumentException('The --since-minutes option must be a valid numeric digit.');
        }

        $query = $repository->getBuilder()
            ->whereNull('completed_at')
            ->whereDate('created_at', '<=', CarbonImmutable::now()->subMinutes($since));

        $count = $query->count();
        if (!$count) {
            $this->info('There are no orphaned backups to be removed.');

            return;
        }

        $this->warn("Deleting {$count} backups that have not been marked as completed in the last {$since} minutes.");

        $query->delete();
    }
}
