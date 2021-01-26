<?php

namespace Pterodactyl\Providers;

use View;
use Cache;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Observers\UserObserver;
use Pterodactyl\Extensions\Themes\Theme;
use Pterodactyl\Observers\ServerObserver;
use Pterodactyl\Observers\SubuserObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        User::observe(UserObserver::class);
        Server::observe(ServerObserver::class);
        Subuser::observe(SubuserObserver::class);

        View::share('appVersion', $this->versionData()['version'] ?? 'undefined');
        View::share('appIsGit', $this->versionData()['is_git'] ?? false);

        Paginator::useBootstrap();
    }

    /**
     * Register application service providers.
     */
    public function register()
    {
        // Only load the settings service provider if the environment
        // is configured to allow it.
        if (!config('pterodactyl.load_environment_only', false) && $this->app->environment() !== 'testing') {
            $this->app->register(SettingsServiceProvider::class);
        }

        $this->app->singleton('extensions.themes', function () {
            return new Theme();
        });
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
