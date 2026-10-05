<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Maintenance;

use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Pterodactyl\Models\Backup;

#[Description('Marks all backups older than "n" minutes that have not yet completed as being failed.')]
#[Signature('p:maintenance:prune-backups {--prune-age=}')]
class PruneOrphanedBackupsCommand extends Command
{
    public function handle(): void
    {
        $since = $this->option('prune-age') ?? config('backups.prune_age', 360);
        $minutes = filter_var($since, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        throw_if($minutes === false, InvalidArgumentException::class, 'The "--prune-age" argument must be a value greater than 0.');

        $query = Backup::query()
            ->whereNull('completed_at')
            ->where('created_at', '<=', CarbonImmutable::now()->subMinutes($minutes)->toDateTimeString());

        $count = $query->count();
        if (! $count) {
            $this->info('There are no orphaned backups to be marked as failed.');

            return;
        }

        $this->warn("Marking $count uncompleted backups that are older than $minutes minutes as failed.");

        $query->update([
            'is_successful' => false,
            'completed_at' => CarbonImmutable::now(),
            'updated_at' => CarbonImmutable::now(),
        ]);
    }
}
