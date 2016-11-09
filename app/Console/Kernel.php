<?php

namespace Pterodactyl\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \Pterodactyl\Console\Commands\Inspire::class,
        \Pterodactyl\Console\Commands\MakeUser::class,
        \Pterodactyl\Console\Commands\ShowVersion::class,
        \Pterodactyl\Console\Commands\UpdateEnvironment::class,
        \Pterodactyl\Console\Commands\RunTasks::class,
        \Pterodactyl\Console\Commands\ClearTasks::class,
        \Pterodactyl\Console\Commands\ClearServices::class,
        \Pterodactyl\Console\Commands\UpdateEmailSettings::class,
        \Pterodactyl\Console\Commands\CleanServiceBackup::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('pterodactyl:tasks')->everyMinute()->withoutOverlapping();
        $schedule->command('pterodactyl:tasks:clearlog')->twiceDaily(3, 15);
    }
}
