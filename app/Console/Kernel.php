<?php

namespace Pterodactyl\Console;

use Illuminate\Console\Scheduling\Schedule;
use Pterodactyl\Console\Commands\InfoCommand;
use Pterodactyl\Console\Commands\User\MakeUserCommand;
use Pterodactyl\Console\Commands\User\DeleteUserCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Pterodactyl\Console\Commands\Server\RebuildServerCommand;
use Pterodactyl\Console\Commands\Location\MakeLocationCommand;
use Pterodactyl\Console\Commands\User\DisableTwoFactorCommand;
use Pterodactyl\Console\Commands\Environment\AppSettingsCommand;
use Pterodactyl\Console\Commands\Location\DeleteLocationCommand;
use Pterodactyl\Console\Commands\Schedule\ProcessRunnableCommand;
use Pterodactyl\Console\Commands\Environment\EmailSettingsCommand;
use Pterodactyl\Console\Commands\Environment\DatabaseSettingsCommand;
use Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        AppSettingsCommand::class,
        CleanServiceBackupFilesCommand::class,
        DatabaseSettingsCommand::class,
        DeleteLocationCommand::class,
        DeleteUserCommand::class,
        DisableTwoFactorCommand::class,
        EmailSettingsCommand::class,
        InfoCommand::class,
        MakeLocationCommand::class,
        MakeUserCommand::class,
        ProcessRunnableCommand::class,
        RebuildServerCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('p:schedule:process')->everyMinute()->withoutOverlapping();
        $schedule->command('p:maintenance:clean-service-backups')->daily();
    }
}
