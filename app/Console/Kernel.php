<?php

namespace Pterodactyl\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('p:schedule:process')->everyMinute()->withoutOverlapping();
        $schedule->command('p:billing:server-scheduler')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('p:maintenance:clean-service-backups')->daily();
        $schedule->command('p:billing:update-hourly-rates')->hourly();
        $schedule->command('p:billing:update-billing-monthly')->monthly();
    }
}
