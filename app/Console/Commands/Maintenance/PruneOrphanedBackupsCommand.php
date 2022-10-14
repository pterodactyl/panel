<?php

namespace Pterodactyl\Console\Commands\Maintenance;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Illuminate\Console\Command;
use Pterodactyl\Repositories\Eloquent\BackupRepository;

class PruneOrphanedBackupsCommand extends Command
{
    protected $signature = 'p:maintenance:prune-backups {--prune-age=}';

    protected $description = 'Marks all backups that have not completed in the last "n" minutes as being failed.';

    /**
     * PruneOrphanedBackupsCommand constructor.
     */
    public function __construct(private BackupRepository $backupRepository)
    {
        parent::__construct();
    }

    public function handle()
    {
        $since = $this->option('prune-age') ?? config('backups.prune_age', 360);
        if (!$since || !is_digit($since)) {
            throw new InvalidArgumentException('The "--prune-age" argument must be a value greater than 0.');
        }

        $query = $this->backupRepository->getBuilder()
            ->whereNull('completed_at')
            ->where('created_at', '<=', CarbonImmutable::now()->subMinutes($since)->toDateTimeString());

        $count = $query->count();
        if (!$count) {
            $this->info('There are no orphaned backups to be marked as failed.');

            return;
        }

        $this->warn("Marking $count backups that have not been marked as completed in the last $since minutes as failed.");

        $query->update([
            'is_successful' => false,
            'completed_at' => CarbonImmutable::now(),
            'updated_at' => CarbonImmutable::now(),
        ]);
    }
}
