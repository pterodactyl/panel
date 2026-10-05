<?php

declare(strict_types=1);

namespace Pterodactyl\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Database\QueryException;
use Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand;
use Pterodactyl\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;
use Pterodactyl\Console\Commands\Schedule\ProcessRunnableCommand;
use Pterodactyl\Contracts\Telemetry\SendsTelemetry;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\Setting;
use Ramsey\Uuid\Uuid;

final class Scheduler
{
    public function __invoke(Schedule $schedule): void
    {
        // https://laravel.com/docs/10.x/upgrade#redis-cache-tags
        $schedule->command('cache:prune-stale-tags')->hourly();

        // Execute scheduled commands for servers every minute, as if there was a normal cron running.
        $schedule->command(ProcessRunnableCommand::class)->everyMinute()->withoutOverlapping(60)->onOneServer();
        $schedule->command(CleanServiceBackupFilesCommand::class)->daily()->withoutOverlapping();

        if (config('backups.prune_age')) {
            // Every 30 minutes, run the backup pruning command so that any abandoned backups can be deleted.
            $schedule->command(PruneOrphanedBackupsCommand::class)->everyThirtyMinutes()->withoutOverlapping();
        }

        if (config('activity.prune_days')) {
            $schedule->command(PruneCommand::class, ['--model' => [ActivityLog::class]])->daily()->withoutOverlapping();
        }

        if (config('pterodactyl.telemetry.enabled')) {
            $this->scheduleTelemetry($schedule);
        }
    }

    /**
     * Push telemetry daily at a time derived from the install uuid.
     */
    private function scheduleTelemetry(Schedule $schedule): void
    {
        try {
            $uuid = Setting::fetch('app:telemetry:uuid');
        } catch (QueryException) {
            return;
        }

        if ($uuid === null) {
            try {
                $uuid = Uuid::uuid4()->toString();
                Setting::put('app:telemetry:uuid', $uuid);
            } catch (QueryException) {
                return;
            }
        }

        $time = hexdec(str_replace('-', '', mb_substr($uuid, 27))) % 1440;
        $hour = intdiv($time, 60);
        $minute = $time % 60;

        $schedule->call(static function (SendsTelemetry $telemetry): void {
            $telemetry->send();
        })->description('Collect Telemetry')->dailyAt(sprintf('%02d:%02d', $hour, $minute))->withoutOverlapping();
    }
}
