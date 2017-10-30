<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Providers;

use View;
use Cache;
use Pterodactyl\Models;
use Pterodactyl\Observers;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Models\User::observe(Observers\UserObserver::class);
        Models\Server::observe(Observers\ServerObserver::class);
        Models\Subuser::observe(Observers\SubuserObserver::class);

        View::share('appVersion', $this->versionData()['version'] ?? 'undefined');
        View::share('appIsGit', $this->versionData()['is_git'] ?? false);
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        if (config('pterodactyl.auth.notifications')) {
            $this->app->register(\DaneEveritt\LoginNotifications\NotificationServiceProvider::class);
        }
    }

    /**
     * Return version information for the footer.
     *
     * @return array
     */
    protected function versionData()
    {
        return Cache::remember('git-version', 5, function () {
            if (file_exists(base_path('.git/HEAD'))) {
                $head = explode(' ', file_get_contents(base_path('.git/HEAD')));

                if (array_key_exists(1, $head)) {
                    $path = base_path('.git/' . trim($head[1]));
                }
            }

            if (isset($path) && file_exists($path)) {
                return [
                    'version' => substr(file_get_contents($path), 0, 8),
                    'is_git' => true,
                ];
            }

            return [
                'version' => config('app.version'),
                'is_git' => false,
            ];
        });
    }
}
