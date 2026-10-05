<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Extensions\Backups\BackupManager;

class BackupsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the S3 backup disk.
     */
    public function register(): void
    {
        $this->app->singleton(BackupManager::class, fn (Application $app): BackupManager => new BackupManager($app));
    }

    /**
     * @return list<string>
     */
    public function provides(): array
    {
        return [BackupManager::class];
    }
}
