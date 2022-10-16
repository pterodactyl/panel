<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;
use Pterodactyl\Extensions\Backups\BackupManager;
use Illuminate\Contracts\Support\DeferrableProvider;

class BackupsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the S3 backup disk.
     */
    public function register()
    {
        $this->app->singleton(BackupManager::class, function ($app) {
            return new BackupManager($app);
        });
    }

    public function provides(): array
    {
        return [BackupManager::class];
    }
}
