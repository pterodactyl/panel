<?php

namespace Pterodactyl\Console;

use Illuminate\Console\Scheduling\Schedule;
use Pterodactyl\Console\Commands\InfoCommand;
use Pterodactyl\Console\Commands\User\MakeUserCommand;
use Pterodactyl\Console\Commands\User\DeleteUserCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Pterodactyl\Console\Commands\Location\MakeLocationCommand;
use Pterodactyl\Console\Commands\User\DisableTwoFactorCommand;
use Pterodactyl\Console\Commands\Location\DeleteLocationCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        DeleteLocationCommand::class,
        DeleteUserCommand::class,
        DisableTwoFactorCommand::class,
        InfoCommand::class,
        MakeLocationCommand::class,
        MakeUserCommand::class,
//        \Pterodactyl\Console\Commands\MakeUser::class,
//        \Pterodactyl\Console\Commands\ShowVersion::class,
//        \Pterodactyl\Console\Commands\UpdateEnvironment::class,
//        \Pterodactyl\Console\Commands\RunTasks::class,
//        \Pterodactyl\Console\Commands\ClearTasks::class,
//        \Pterodactyl\Console\Commands\ClearServices::class,
//        \Pterodactyl\Console\Commands\UpdateEmailSettings::class,
//        \Pterodactyl\Console\Commands\CleanServiceBackup::class,
//        \Pterodactyl\Console\Commands\AddNode::class,
//        \Pterodactyl\Console\Commands\MakeLocationCommand::class,
//        \Pterodactyl\Console\Commands\RebuildServer::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('pterodactyl:tasks')->everyMinute()->withoutOverlapping();
        $schedule->command('pterodactyl:tasks:clearlog')->twiceDaily(3, 15);
        $schedule->command('pterodactyl:cleanservices')->twiceDaily(1, 13);
    }
}
