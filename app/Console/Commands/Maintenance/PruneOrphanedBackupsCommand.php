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
    protected $description = 'Marks all backups that have not completed in the last "n" minutes as being failed.';

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
            $this->info('There are no orphaned backups to be marked as failed.');

            return;
        }

        $this->warn("Marking {$count} backups that have not been marked as completed in the last {$since} minutes as failed.");

        $query->update([
            'is_successful' => false,
            'completed_at' => CarbonImmutable::now(),
            'updated_at' => CarbonImmutable::now(),
        ]);
    }
}
